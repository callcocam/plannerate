<script setup lang="ts">
/**
 * Componente Sections para gerenciamento de seções de uma gôndola em um planograma
 * Permite visualizar, ordenar e manipular seções com prateleiras
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
 * @property {Section[]} sections - Array de seções a serem exibidas
 * @property {number} scaleFactor - Fator de escala para dimensionamento visual
 * @property {number} baseHeight - Altura base da gôndola
 * @property {Gondola} gondola - Objeto com propriedades da gôndola
 * @property {string} shelfDirection - Direção das prateleiras ('top' ou 'bottom')
 */
interface SectionProps {
    sections: Section[];
    scaleFactor: number;
    baseHeight: number;
    gondola: Gondola;
    shelfDirection: 'top' | 'bottom';
}

// Definição das propriedades (props) do componente
const props = defineProps<SectionProps>();
/**
 * Definição dos eventos emitidos pelo componente
 * Permite comunicação com componentes pai
 */
const emit = defineEmits<{
    'update:sections': [sections: Section[]]; // Atualiza as seções
    remove: [section: Section]; // Remove uma seção
    add: []; // Adiciona uma nova seção
    'select-shelf': [shelf: ShelfType]; // Seleciona uma prateleira
    'update:layer': [layer: Layer]; // Atualiza uma camada
    'update:segment': [segment: Segment]; // Atualiza um segmento
}>();

// Desestruturação das props com toRefs para manter a reatividade
const { sections, scaleFactor, baseHeight, shelfDirection } = toRefs(props);

// Referência local para as seções, para evitar modificar as props diretamente
const localSections = ref<Section[]>([]);

// Estado para rastrear a prateleira atualmente selecionada
const activeShelf = ref<ShelfType | null>(null);

// Disponibiliza a prateleira ativa via provide/inject API
provide('activeShelf', activeShelf);

/**
 * Inicializa as seções locais no momento da montagem do componente
 * Usa JSON.parse/stringify para criar uma cópia profunda e quebrar a cadeia de reatividade
 */
onMounted(() => {
    if (props.sections) {
        localSections.value = JSON.parse(JSON.stringify(props.sections));
    }

    // Inicializa o Sortable em um próximo ciclo para garantir que o DOM está pronto
    nextTick(() => {
        initializeSortable();
    });
});

/**
 * Observa mudanças nas props sections e atualiza a referência local
 * Evita atualizações circulares verificando se há diferenças reais
 */
watch(
    () => props.sections,
    (newSections) => {
        if (newSections) {
            const rawNewSections = JSON.parse(JSON.stringify(newSections));
            const rawCurrentSections = JSON.parse(JSON.stringify(localSections.value));

            // Só atualiza se houver diferença real para evitar atualizações circulares
            if (JSON.stringify(rawNewSections) !== JSON.stringify(rawCurrentSections)) {
                localSections.value = rawNewSections;
            }
        }
    },
    { deep: true }, // Observa mudanças profundas no objeto
);

/**
 * Emite eventos quando as seções locais são alteradas
 * Usa uma flag para evitar loops infinitos de atualização
 */
let isEmitting = false;
watch(
    localSections,
    (newSections) => {
        if (!isEmitting) {
            isEmitting = true;
            // Emite o evento com uma cópia profunda para evitar problemas de reatividade
            emit('update:sections', JSON.parse(JSON.stringify(newSections)));
            // Reseta a flag após um microtask para permitir novas emissões
            setTimeout(() => {
                isEmitting = false;
            }, 0);
        }
    },
    { deep: true },
);

// Referência ao elemento DOM que contém as seções (para Sortable.js)
const sectionsContainer = ref<HTMLElement | null>(null);
let sortableInstance: Sortable | null = null;

/**
 * Inicializa o Sortable.js para permitir arrastar e soltar seções
 */
function initializeSortable() {
    if (!sectionsContainer.value) return;

    // Limpar instância anterior se existir
    if (sortableInstance) {
        sortableInstance.destroy();
    }

    // Criar nova instância do Sortable
    sortableInstance = Sortable.create(sectionsContainer.value, {
        animation: 150,
        ghostClass: 'section-ghost', // Classe para o fantasma durante o arrasto
        chosenClass: 'section-chosen', // Classe para o item escolhido
        dragClass: 'section-drag', // Classe para o item enquanto arrastado
        handle: '.section-drag-handle', // Alça para arrastar
        draggable: '.section-item', // Seletor para itens arrastáveis
        onEnd: (evt) => {
            if (evt.oldIndex === undefined || evt.newIndex === undefined) return;

            // Cria uma cópia da lista de seções
            const sectionsList = [...localSections.value];

            // Remove o item da posição antiga e o insere na nova posição
            const movedItem = sectionsList.splice(evt.oldIndex, 1)[0];
            sectionsList.splice(evt.newIndex, 0, movedItem);

            // Atualiza a propriedade position de cada seção
            sectionsList.forEach((section, index) => {
                section.ordering = index;
            });

            // Atualiza a referência local
            localSections.value = sectionsList;
            localSections.value.forEach((section, index) => {
                router.put(route('sections.update', section.id), section, {
                    preserveScroll: true,
                    onSuccess: () => {
                        // console.log('Seção atualizada com sucesso:', section);
                    },
                    onError: (errors) => {
                        console.error('Erro ao atualizar seção:', errors);
                    },
                });
            });
            // Emite o evento de atualização

            console.log('Seções atualizadas:', sectionsList);
        },
    });
}

/**
 * Calcula o estilo CSS para cada seção com base em suas propriedades
 * Aplica o fator de escala para dimensionamento visual
 * @param {Section} section - A seção para a qual calcular o estilo
 * @param {number} index - O índice da seção no array
 * @returns {Object} Objeto com estilos CSS a serem aplicados
 */
function getSectionStyle(section: Section, index: number): Record<string, string> {
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
 * Baseado nos espaçamentos dos furos da gramalheira
 * @returns {number[]} Array com as posições disponíveis em pixels
 */
const availablePositions = computed((): number[] => {
    if (!props.gondola) return [];

    const positions: number[] = [];
    const holeSpacing = props.gondola.hole_spacing;
    const totalPositions = Math.floor((props.gondola.height - props.gondola.base_height) / holeSpacing);

    // Gera as posições baseadas no espaçamento dos furos
    for (let i = 0; i < totalPositions; i++) {
        positions.push(holeSpacing * i);
    }

    return positions;
});

/**
 * Interface que estende ShelfType para incluir uma referência à seção
 * e a posição alinhada ao furo mais próximo
 */
interface ShelfWithSection extends ShelfType {
    section: Section;
    alignedPosition: number;
}

/**
 * Mapeia todas as prateleiras de todas as seções
 * Calcula a posição alinhada ao furo mais próximo para cada prateleira
 */
const shelves = computed((): ShelfWithSection[] => {
    return props.sections.reduce((acc: ShelfWithSection[], section) => {
        const sectionShelves = section.shelves.map((shelf) => {
            // Encontra a posição disponível mais próxima da posição atual
            const closestPosition = findClosestPosition(shelf.position, availablePositions.value);

            return {
                ...shelf,
                section, // Referência à seção a que pertence
                alignedPosition: closestPosition, // Posição alinhada ao furo mais próximo
            };
        });
        return [...acc, ...sectionShelves];
    }, []);
});

/**
 * Encontra a posição disponível mais próxima de uma posição dada
 * @param {number} currentPosition - Posição atual
 * @param {number[]} availablePositions - Array de posições disponíveis
 * @returns {number} A posição disponível mais próxima
 */
function findClosestPosition(currentPosition: number, availablePositions: number[]): number {
    if (availablePositions.length === 0) return currentPosition;

    let closest = availablePositions[0];
    let minDistance = Math.abs(currentPosition - closest);

    // Percorre todas as posições disponíveis para encontrar a mais próxima
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
 * @param {ShelfWithSection} shelf - A prateleira a verificar
 * @param {string} sectionId - ID da seção a comparar
 * @returns {boolean} Verdadeiro se a prateleira pertence à seção
 */
const isShelfInSection = (shelf: ShelfWithSection, sectionId: string): boolean => {
    return shelf.section.id === sectionId;
};

/**
 * Verifica se uma prateleira é a última em sua seção
 * @param {ShelfWithSection} shelf - A prateleira a verificar
 * @param {string} sectionId - ID da seção a comparar
 * @returns {boolean} Verdadeiro se a prateleira é a última da seção
 */
const isLastShelfInSection = (shelf: ShelfWithSection, sectionId: string): boolean => {
    const sectionShelves = shelves.value.filter((s) => isShelfInSection(s, sectionId));
    return shelf.id === sectionShelves[sectionShelves.length - 1].id;
};

/**
 * Verifica se uma prateleira é a primeira em sua seção
 * @param {ShelfWithSection} shelf - A prateleira a verificar
 * @param {string} sectionId - ID da seção a comparar
 * @returns {boolean} Verdadeiro se a prateleira é a primeira da seção
 */
const isFirstShelfInSection = (shelf: ShelfWithSection, sectionId: string): boolean => {
    const sectionShelves = shelves.value.filter((s) => isShelfInSection(s, sectionId));
    return shelf.id === sectionShelves[0].id;
};

/**
 * Atualiza uma camada (layer) associada a uma prateleira
 * @param {ShelfType} updatedShelf - A prateleira com a camada atualizada
 */
const updateLayer = (updatedShelf: ShelfType): void => {
    // Encontra a seção à qual a prateleira pertence
    const sectionId = updatedShelf.section.id;

    // Faz uma cópia profunda das seções atuais para evitar problemas de reatividade
    const sectionsCopy = JSON.parse(JSON.stringify(localSections.value));

    // Atualiza a cópia
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

    // Atualiza o estado local com a cópia modificada
    localSections.value = updatedSections;
};

/**
 * Atualiza um segmento associado a uma prateleira
 * @param {ShelfType} updatedShelf - A prateleira com o segmento atualizado
 */
const updateSegment = (updatedShelf: ShelfType): void => {
    // Implementação similar ao updateLayer
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
};

/**
 * Envia uma requisição para atualizar uma prateleira no servidor
 * Usa Inertia.js para fazer a requisição AJAX
 * @param {ShelfWithSection} shelf - A prateleira a ser atualizada
 */
const updateShelf = (shelf: ShelfWithSection): void => {
    const data: any = {
        ...shelf,
    };
    // Envia a requisição PUT para atualizar a prateleira
    delete data.settings; // Remove as configurações para evitar problemas de serialização

    router.put(`/shelves/${shelf.id}`, data, {
        preserveState: shelf?.preserveState, // Não preserva o estado atual
        preserveScroll: true, // Mantém a posição de rolagem
        onSuccess: ({ props }) => {}, // Callback de sucesso
        onError: (errors) => {
            console.error('Failed to update shelf:', errors);
        },
    });
};

/**
 * Remove uma prateleira da seção
 * @param {ShelfWithSection} shelf - A prateleira a ser removida
 */
const deleteShelf = (shelf: ShelfWithSection): void => {
    // Encontra a seção à qual a prateleira pertence
    const sectionId = shelf.section.id;
    // Faz uma cópia profunda das seções atuais para evitar problemas de reatividade
    const sectionsCopy = JSON.parse(JSON.stringify(localSections.value));
    // Atualiza a cópia, removendo a prateleira
    const updatedSections = sectionsCopy.map((section: Section) => {
        if (section.id === sectionId) {
            return {
                ...section,
                shelves: section.shelves.filter((s) => s.id !== shelf.id),
            };
        }
        return section;
    });
    // Atualiza o estado local com a cópia modificada
    localSections.value = updatedSections;
};

/**
 * Duplica uma prateleira existente
 * @param {ShelfWithSection} newShelf - A nova prateleira a ser adicionada
 */
const duplicateShelf = (newShelf: ShelfWithSection): void => {
    // Adiciona a nova prateleira à seção correspondente
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
 * @param {Object} transferData - Dados de transferência da prateleira
 * @param {ShelfType} transferData.shelf - A prateleira a ser transferida
 * @param {string} transferData.fromSectionId - ID da seção de origem
 * @param {string} transferData.toSectionId - ID da seção de destino
 * @param {number} transferData.position - Nova posição na seção de destino
 */
const transferShelf = (transferData: { shelf: ShelfType; fromSectionId: string; toSectionId: string; position?: number }): void => {
    const { shelf, fromSectionId, toSectionId, position } = transferData;

    // Se for a mesma seção, não faz nada
    if (fromSectionId === toSectionId) return;
    // Envia atualização para o servidor
    router.put(
        route('shelves.update-section', shelf.id),
        {
            section_id: toSectionId,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                // Atualiza a seção localmente
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
 * @param {ShelfType} shelf - A prateleira selecionada
 */
const selectShelf = (shelf: ShelfType): void => {
    // Define a prateleira ativa
    activeShelf.value = shelf;

    // Opcionalmente, emitir evento para componentes de nível superior
    emit('select-shelf', shelf);
};

// Função para desselecionar todas as prateleiras
const clearShelfSelection = () => {
    activeShelf.value = null;
};

// Adicionar handler para cliques no container para limpar seleção
const handleSectionContainerClick = (event) => {
    // Verifica se o clique foi diretamente no container e não em uma prateleira
    if (event.target === event.currentTarget) {
        clearShelfSelection();
    }
};
</script>
<template>
    <!-- Container principal para as seções, com referência para Sortable.js -->
    <div class="relative flex min-h-screen items-center" ref="sectionsContainer" @click="handleSectionContainerClick">
        <!-- Mensagem quando não há seções -->
        <div v-if="localSections.length === 0" class="flex w-full flex-col items-center justify-center">
            <p class="mb-4 text-center text-gray-500 dark:text-gray-400">Não há seções para exibir. Adicione uma nova seção para começar.</p>
            <Button variant="outline" @click="emit('add')"> Adicionar Primeira Seção </Button>
        </div>

        <!-- Loop pelas seções para renderizá-las -->
        <div 
            v-for="(section, index) in localSections" 
            :key="section.id" 
            class="section-item relative"
            :data-section-id="section.id"
        >
            <SectionContext :section="section" :gondola="gondola" @transfer-shelf="transferShelf">
                <!-- Item de seção arrastável -->
                <div :style="getSectionStyle(section, index)" class="section-content">
                    <!-- Alça de arrasto para poder arrastar a seção -->
                    <div class="section-drag-handle">
                        <Move class="h-4 w-4 cursor-grab" />
                    </div>

                    <!-- Gramalheira esquerda (suporte vertical) -->
                    <Gramalheira
                        orientation="vertical"
                        class="absolute top-0"
                        :style="contentGramalheiraStyle"
                        :gondola="gondola"
                        :scaleFactor="scaleFactor"
                        v-if="!index"
                    />

                    <!-- Área das prateleiras -->
                    <div class="relative">
                        <!-- Seção de prateleiras -->
                        <!-- Loop pelas prateleiras da seção atual -->
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

                    <!-- Gramalheira direita (suporte vertical) -->
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
/* Estilo para os itens de seção */
.section-item {
    transition: all 0.2s ease;
    margin: 0;
}

/* Estilo para a alça de arrasto */
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

/* Estilos para os estados de arrasto */
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

/* Estilo aprimorado para seção alvo durante arrasto */
.potential-drop-target {
  outline: 3px dashed #3b82f6 !important;
  outline-offset: -2px;
  background-color: rgba(59, 130, 246, 0.1);
  position: relative;
  z-index: 10;
  transition: all 0.2s ease-in-out;
}

.potential-drop-target::before {
  content: "Soltar aqui";
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background-color: #3b82f6;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 14px;
  font-weight: bold;
  z-index: 11;
  pointer-events: none;
  opacity: 0.9;
  white-space: nowrap;
}

.potential-drop-target::after {
  content: "";
  position: absolute;
  inset: 0;
  background-color: rgba(59, 130, 246, 0.1);
  pointer-events: none;
  z-index: 1;
}

/* Melhoria para o comportamento da seção durante o hover/arrasto */
.section-item {
  transition: all 0.2s ease;
  margin: 0;
  position: relative;
}

.section-content {
  position: relative;
  transition: transform 0.2s ease-in-out;
}

.potential-drop-target .section-content {
  transform: scale(1.02);
}
</style>
