<script setup lang="ts">
/**
 * Componente SectionList otimizado para gerenciamento de seções de uma gôndola
 */
// @ts-nocheck
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';
import { Move } from 'lucide-vue-next';
import Sortable from 'sortablejs';
import { computed, nextTick, onMounted, provide, ref, toRefs, watch } from 'vue';
import { route } from 'ziggy-js';
import SectionContext from './context/SectionContext.vue';
import Gramalheira from './Gramalheira.vue';
import { Gondola, Layer, Section, Segment, Shelf as ShelfType } from './planogram';
import Shelf from './Shelf.vue';

/**
 * Interface que define as propriedades do componente
 */
interface SectionProps {
    sections: Section[];
    scaleFactor: number;
    baseHeight: number;
    gondola: Gondola;
    shelfDirection: 'top' | 'bottom';
}

// Definição das propriedades do componente
const props = defineProps<SectionProps>();

// Eventos emitidos pelo componente
const emit = defineEmits<{
    'update:sections': [sections: Section[]];
    remove: [section: Section];
    add: [];
    'select-shelf': [shelf: ShelfType];
    'update:layer': [layer: Layer];
    'update:segment': [segment: Segment];
}>();

// Desestruturação das props com toRefs para manter a reatividade
const { sections, scaleFactor, baseHeight, shelfDirection } = toRefs(props);

// Referência local para as seções
const localSections = ref<Section[]>([]);

// Estado para rastrear a prateleira atualmente selecionada
const activeShelf = ref<ShelfType | null>(null);

// Disponibiliza a prateleira ativa via provide/inject API
provide('activeShelf', activeShelf);

/**
 * Inicializa as seções locais no momento da montagem do componente
 */
onMounted(() => {
    if (props.sections) {
        localSections.value = JSON.parse(JSON.stringify(props.sections));
    }

    nextTick(() => {
        initializeSortable();
    });
});

/**
 * Observa mudanças nas props sections e atualiza a referência local
 */
watch(
    () => props.sections,
    (newSections) => {
        if (newSections) {
            const rawNewSections = JSON.parse(JSON.stringify(newSections));
            const rawCurrentSections = JSON.parse(JSON.stringify(localSections.value));

            if (JSON.stringify(rawNewSections) !== JSON.stringify(rawCurrentSections)) {
                localSections.value = rawNewSections;
            }
        }
    },
    { deep: true },
);

/**
 * Emite eventos quando as seções locais são alteradas
 */
let isEmitting = false;
watch(
    localSections,
    (newSections) => {
        if (!isEmitting) {
            isEmitting = true;
            emit('update:sections', JSON.parse(JSON.stringify(newSections)));
            setTimeout(() => {
                isEmitting = false;
            }, 0);
        }
    },
    { deep: true },
);

// Referência ao elemento DOM que contém as seções
const sectionsContainer = ref<HTMLElement | null>(null);
let sortableInstance: Sortable | null = null;

/**
 * Inicializa o Sortable.js para permitir arrastar e soltar seções
 */
function initializeSortable() {
    if (!sectionsContainer.value) return;

    if (sortableInstance) {
        sortableInstance.destroy();
    }

    sortableInstance = Sortable.create(sectionsContainer.value, {
        animation: 150,
        ghostClass: 'section-ghost',
        chosenClass: 'section-chosen',
        dragClass: 'section-drag',
        handle: '.section-drag-handle',
        draggable: '.section-item',
        onEnd: (evt) => {
            if (evt.oldIndex === undefined || evt.newIndex === undefined) return;

            const sectionsList = [...localSections.value];
            const movedItem = sectionsList.splice(evt.oldIndex, 1)[0];
            sectionsList.splice(evt.newIndex, 0, movedItem);

            sectionsList.forEach((section, index) => {
                section.ordering = index;
            });

            localSections.value = sectionsList;

            // Atualizar no servidor
            const updates = sectionsList.map((section, index) => ({
                id: section.id,
                ordering: index,
            }));

            router.put(
                route('sections.reorder', {
                    gondola: props.gondola.id,
                }),
                { sections: updates },
                {
                    preserveScroll: true,
                    onError: (errors) => {
                        console.error('Erro ao reordenar seções:', errors);
                    },
                },
            );
        },
    });
}

/**
 * Calcula o estilo CSS para cada seção
 */
function getSectionStyle(section: Section): Record<string, string> {
    const width = section.width * scaleFactor.value;
    const height = section.height * scaleFactor.value;

    return {
        width: `${width}px`,
        height: `${height}px`,
        position: 'relative',
        boxShadow: '0 1px 3px 0 rgba(0, 0, 0, 0.1)',
        display: 'flex',
        flexDirection: 'column',
    };
}

/**
 * Calcula as posições disponíveis para as prateleiras
 */
const availablePositions = computed((): number[] => {
    if (!props.gondola) return [];

    const positions: number[] = [];
    const holeSpacing = props.gondola.hole_spacing;
    const totalPositions = Math.floor((props.gondola.height - props.gondola.base_height) / holeSpacing);

    for (let i = 0; i < totalPositions; i++) {
        positions.push(holeSpacing * i);
    }

    return positions;
});

/**
 * Interface que estende ShelfType para incluir referências adicionais
 */
interface ShelfWithSection extends ShelfType {
    section: Section;
    alignedPosition: number;
}

/**
 * Mapeia todas as prateleiras de todas as seções
 */
const shelves = computed((): ShelfWithSection[] => {
    return props.sections.reduce((acc: ShelfWithSection[], section) => {
        const sectionShelves = section.shelves.map((shelf) => {
            const closestPosition = findClosestPosition(shelf.position, availablePositions.value);

            return {
                ...shelf,
                section,
                alignedPosition: closestPosition,
            };
        });
        return [...acc, ...sectionShelves];
    }, []);
});

/**
 * Encontra a posição disponível mais próxima
 */
function findClosestPosition(currentPosition: number, availablePositions: number[]): number {
    if (availablePositions.length === 0) return currentPosition;

    let closest = availablePositions[0];
    let minDistance = Math.abs(currentPosition - closest);

    for (let i = 1; i < availablePositions.length; i++) {
        const distance = Math.abs(currentPosition - availablePositions[i]);
        if (distance < minDistance) {
            minDistance = distance;
            closest = availablePositions[i];
        }
    }

    return closest;
}

/**
 * Calcula a altura da gôndola aplicando o fator de escala
 */
const gondolaHeight = computed((): number => {
    return props.gondola.height * scaleFactor.value;
});

/**
 * Calcula o estilo para as gramalheiras (suportes verticais)
 */
const contentGramalheiraStyle = computed((): Record<string, string> => {
    return {
        top: `${props.gondola.hole_spacing * scaleFactor.value}px`,
        width: `${props.gondola.thickness * scaleFactor.value}px`,
        height: `${gondolaHeight.value}px`,
    };
});

/**
 * Verifica se uma prateleira pertence a uma determinada seção
 */
const isShelfInSection = (shelf: ShelfWithSection, sectionId: string): boolean => {
    return shelf.section.id === sectionId;
};

/**
 * Verifica se uma prateleira é a última em sua seção
 */
const isLastShelfInSection = (shelf: ShelfWithSection, sectionId: string): boolean => {
    const sectionShelves = shelves.value.filter((s) => isShelfInSection(s, sectionId));
    return shelf.id === sectionShelves[sectionShelves.length - 1].id;
};

/**
 * Verifica se uma prateleira é a primeira em sua seção
 */
const isFirstShelfInSection = (shelf: ShelfWithSection, sectionId: string): boolean => {
    const sectionShelves = shelves.value.filter((s) => isShelfInSection(s, sectionId));
    return shelf.id === sectionShelves[0].id;
};

/**
 * Atualiza uma camada (layer) associada a uma prateleira
 */
const updateLayer = (updatedShelf: ShelfType): void => {
    const sectionId = updatedShelf.section.id;
    const sectionsCopy = JSON.parse(JSON.stringify(localSections.value));

    const updatedSections = sectionsCopy.map((section: Section) => {
        if (section.id === sectionId) {
            return {
                ...section,
                shelves: section.shelves.map((shelf) => {
                    if (shelf.id === updatedShelf.id) {
                        return updatedShelf;
                    }
                    return shelf;
                }),
            };
        }
        return section;
    });

    localSections.value = updatedSections;
    emit('update:layer', updatedShelf);
};

/**
 * Atualiza um segmento associado a uma prateleira
 */
const updateSegment = (updatedShelf: ShelfType): void => {
    const sectionId = updatedShelf.section.id;
    const sectionsCopy = JSON.parse(JSON.stringify(localSections.value));

    const updatedSections = sectionsCopy.map((section: Section) => {
        if (section.id === sectionId) {
            return {
                ...section,
                shelves: section.shelves.map((shelf) => {
                    if (shelf.id === updatedShelf.id) {
                        return updatedShelf;
                    }
                    return shelf;
                }),
            };
        }
        return section;
    });

    localSections.value = updatedSections;
    emit('update:segment', updatedShelf);
};

/**
 * Envia requisição para atualizar uma prateleira no servidor
 */
const updateShelf = (shelf: ShelfWithSection): void => {
    const data = { ...shelf };
    delete data.settings; 
    router.put(`/shelves/${shelf.id}`, data, {
        preserveState: data.preserveState,
        preserveScroll: true,
        onError: (errors) => {
            console.error('Falha ao atualizar prateleira:', errors);
        },
    });
};

/**
 * Remove uma prateleira da seção
 */
const deleteShelf = (shelf: ShelfWithSection): void => {
    const sectionId = shelf.section.id;
    const sectionsCopy = JSON.parse(JSON.stringify(localSections.value));

    const updatedSections = sectionsCopy.map((section: Section) => {
        if (section.id === sectionId) {
            return {
                ...section,
                shelves: section.shelves.filter((s) => s.id !== shelf.id),
            };
        }
        return section;
    });

    localSections.value = updatedSections;
};

/**
 * Duplica uma prateleira existente
 */
const duplicateShelf = (newShelf: ShelfWithSection): void => {
    localSections.value = localSections.value.map((section) => {
        if (section.id === newShelf.section.id) {
            return {
                ...section,
                shelves: [...section.shelves, newShelf],
            };
        }
        return section;
    });
};

/**
 * Transfere uma prateleira de uma seção para outra
 */
const transferShelf = (transferData: { shelf: ShelfType; fromSectionId: string; toSectionId: string; position?: number }): void => {
    const { shelf, fromSectionId, toSectionId } = transferData;

    if (fromSectionId === toSectionId) return;

    router.put(
        route('shelves.update-section', shelf.id),
        {
            section_id: toSectionId,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                localSections.value = localSections.value.map((section) => {
                    if (section.id === fromSectionId) {
                        return {
                            ...section,
                            shelves: section.shelves.filter((s) => s.id !== shelf.id),
                        };
                    }
                    if (section.id === toSectionId) {
                        return {
                            ...section,
                            shelves: [...section.shelves, { ...shelf }],
                        };
                    }
                    return section;
                });
            },
            onError: (errors) => {
                console.error('Erro ao transferir prateleira:', errors);
            },
        },
    );
};

/**
 * Manipula a seleção de uma prateleira
 */
const selectShelf = (shelf: ShelfType): void => {
    activeShelf.value = shelf;
    emit('select-shelf', shelf);
};

/**
 * Limpa a seleção de prateleiras
 */
const clearShelfSelection = () => {
    activeShelf.value = null;
};

/**
 * Trata cliques no container para limpar seleção
 */
const handleSectionContainerClick = (event) => {
    if (event.target === event.currentTarget) {
        clearShelfSelection();
    }
};
</script>

<template>
    <!-- Container principal para as seções -->
    <div class="relative flex min-h-screen items-center" ref="sectionsContainer" @click="handleSectionContainerClick">
        <!-- Mensagem quando não há seções -->
        <div v-if="localSections.length === 0" class="flex w-full flex-col items-center justify-center">
            <p class="mb-4 text-center text-gray-500 dark:text-gray-400">Não há seções para exibir. Adicione uma nova seção para começar.</p>
            <Button variant="outline" @click="emit('add')">Adicionar Primeira Seção</Button>
        </div>

        <!-- Loop pelas seções -->
        <div v-for="(section, index) in localSections" :key="section.id" class="section-item relative" :data-section-id="section.id">
            <SectionContext :section="section" :gondola="gondola" @transfer-shelf="transferShelf">
                <!-- Item de seção arrastável -->
                <div :style="getSectionStyle(section)" class="section-content">
                    <!-- Alça de arrasto -->
                    <div class="section-drag-handle">
                        <Move class="h-4 w-4 cursor-grab" />
                    </div>

                    <!-- Gramalheira esquerda -->
                    <Gramalheira
                        v-if="!index"
                        orientation="vertical"
                        class="absolute top-0"
                        :style="contentGramalheiraStyle"
                        :gondola="gondola"
                        :scaleFactor="scaleFactor"
                    />

                    <!-- Área das prateleiras -->
                    <div class="relative">
                        <Shelf
                            v-for="shelf in shelves.filter((s) => isShelfInSection(s, section.id))"
                            :key="shelf.id"
                            :gondola="gondola"
                            :shelf="shelf"
                            :shelf-direction="shelfDirection"
                            :scale-factor="scaleFactor"
                            @update:shelf="updateShelf"
                            @update:layer="updateLayer"
                            @update:segment="updateSegment"
                            @delete="deleteShelf"
                            @duplicate="duplicateShelf"
                            @transfer-shelf="transferShelf"
                            @selectShelf="selectShelf"
                            :is-last="isLastShelfInSection(shelf, section.id)"
                            :is-first="isFirstShelfInSection(shelf, section.id)"
                        />
                    </div>

                    <!-- Gramalheira direita -->
                    <Gramalheira
                        orientation="vertical"
                        class="absolute -right-1 top-0"
                        :style="contentGramalheiraStyle"
                        :gondola="gondola"
                        :scaleFactor="scaleFactor"
                    />
                </div>
            </SectionContext>
        </div>
    </div>
</template>

<style scoped>
.section-item {
    transition: all 0.2s ease;
    margin: 0;
    position: relative;
}

.section-drag-handle {
    position: absolute;
    left: 50%;
    top: 0;
    transform: translate(-50%, -40%);
    z-index: 10;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #4b5563;
    color: white;
    border-radius: 2px;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.section-item:hover .section-drag-handle {
    opacity: 0.8;
}

.section-drag-handle:hover {
    opacity: 1 !important;
}

.section-ghost {
    opacity: 0.5;
    border: 1px dashed #6b7280;
}

.section-chosen {
    z-index: 10;
}

.section-drag {
    z-index: 20;
}

.section-content {
    position: relative;
    transition: transform 0.2s ease-in-out;
}
</style>
