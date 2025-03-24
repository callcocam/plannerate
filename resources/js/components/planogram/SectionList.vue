<script setup lang="ts">
/**
 * Componente Sections para gerenciamento de seções de uma gôndola em um planograma
 * Permite visualizar, ordenar e manipular seções com prateleiras
 */
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';
import Sortable from 'sortablejs';
import { computed, onMounted, ref, toRefs, watch } from 'vue';
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

/**
 * Inicializa as seções locais no momento da montagem do componente
 * Usa JSON.parse/stringify para criar uma cópia profunda e quebrar a cadeia de reatividade
 */
onMounted(() => {
    if (props.sections) {
        localSections.value = JSON.parse(JSON.stringify(props.sections));
    }
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
 * Configura o comportamento de reorganização das seções
 */
onMounted(() => {
    if (sectionsContainer.value) {
        sortableInstance = Sortable.create(sectionsContainer.value, {
            animation: 150, // Duração da animação em ms
            ghostClass: 'opacity-50', // Classe aplicada ao elemento fantasma durante arrasto
            handle: '.section-handle', // Seletor para o elemento que pode ser arrastado
            draggable: '.section-item', // Seletor para elementos arrastáveis
            // Callback quando o arrasto termina
            onEnd: (event: Sortable.SortableEvent) => {
                // Cria uma cópia da lista de seções
                const sectionsList = [...localSections.value];
                // Remove o item da posição antiga e o insere na nova posição
                const movedItem = sectionsList.splice(event.oldIndex!, 1)[0];
                sectionsList.splice(event.newIndex!, 0, movedItem);

                // Atualiza a propriedade position de cada seção
                sectionsList.forEach((section, index) => {
                    section.position = index;
                });

                // Atualiza a referência local e emite o evento
                localSections.value = sectionsList;
                emit('update:sections', sectionsList);
            },
        });
    }
});

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
        preserveState: false, // Não preserva o estado atual
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
</script>

<template>
    <!-- Container principal para as seções, com referência para Sortable.js -->
    <div class="relative flex   min-h-screen items-center overflow-hidden" ref="sectionsContainer">
        <!-- Mensagem quando não há seções -->
        <div v-if="localSections.length === 0" class="flex w-full flex-col items-center justify-center">
            <p class="mb-4 text-center text-gray-500 dark:text-gray-400">Não há seções para exibir. Adicione uma nova seção para começar.</p>
            <Button variant="outline" @click="emit('add')"> Adicionar Primeira Seção </Button>
        </div>

        <!-- Loop pelas seções para renderizá-las -->
        <SectionContext v-for="(section, index) in localSections" :key="section.id" :section="section" :gondola="gondola">
            <!-- Item de seção arrastável -->
            <div class="section-item " :style="getSectionStyle(section, index)">
                <!-- Gramalheira esquerda (suporte vertical) -->
                <Gramalheira
                    orientation="vertical"
                    class="absolute top-0"
                    :style="contentGramalheiraStyle"
                    :gondola="gondola"
                    :scaleFactor="scaleFactor"
                />

                <!-- Área das prateleiras -->
                <div class="relative" >
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
                        :is-last="isLastShelfInSection(shelf, section.id)"
                        :is-first="isFirstShelfInSection(shelf, section.id)"
                    />
                </div>

                <!-- Gramalheira direita (suporte vertical) -->
                <Gramalheira
                    orientation="vertical"
                    class="absolute right-0 top-0"
                    :style="contentGramalheiraStyle"
                    :gondola="gondola"
                    :scaleFactor="scaleFactor"
                />
            </div>
        </SectionContext>
    </div>
</template>

<style scoped>
/* Estilo para os itens de seção */
.section-item {
    transition: all 0.2s ease; /* Transição suave para efeitos visuais */
}

/* Efeito hover nos itens de seção */
.section-item:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Sombra mais pronunciada ao passar o mouse */
}

/* Cursor para áreas arrastáveis */
.section-handle {
    cursor: grab; /* Cursor de "pegar" */
}

/* Cursor quando arrastando ativamente */
.section-handle:active {
    cursor: grabbing; /* Cursor de "arrastando" */
}
</style>
