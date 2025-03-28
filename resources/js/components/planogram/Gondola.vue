<template>
    <div class="mx-auto w-full space-y-2 sm:px-6 lg:px-8">
        <!-- Card de Dimensões -->
        <div class="sticky top-0 z-50 border-b bg-white shadow-sm dark:bg-gray-800">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col items-center space-x-8 md:flex-row">
                        <h3 class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"
                                />
                            </svg>
                            Dimensões da Gôndola
                        </h3>

                        <div class="flex items-center space-x-6">
                            <div class="flex items-center">
                                <label class="mr-2 text-sm text-gray-600 dark:text-gray-400">Largura:</label>
                                <div class="relative flex w-full max-w-sm items-center">
                                    <SmallInput v-model="record.width" disabled />
                                    <span class="ml-1 text-sm text-gray-500 dark:text-gray-400">cm</span>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <label class="mr-2 text-sm text-gray-600 dark:text-gray-400">Altura:</label>
                                <div class="relative flex w-full max-w-sm items-center">
                                    <SmallInput
                                        v-model="record.height"
                                        class="w-20 rounded-md border border-gray-300 px-2 py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        disabled
                                    />
                                    <span class="ml-1 text-sm text-gray-500 dark:text-gray-400">cm</span>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <label class="mr-2 text-sm text-gray-600 dark:text-gray-400">Base:</label>
                                <div class="relative flex w-full max-w-sm items-center">
                                    <SmallInput
                                        v-model="record.base_height"
                                        class="w-20 rounded-md border border-gray-300 px-2 py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        disabled
                                    />
                                    <span class="ml-1 text-sm text-gray-500 dark:text-gray-400">cm</span>
                                </div>
                            </div>
                        </div>

                        <!-- Controle de Escala -->
                        <div class="flex items-center space-x-2">
                            <label class="text-sm text-gray-600 dark:text-gray-400">Escala:</label>
                            <div class="flex items-center space-x-2">
                                <Button
                                    type="buttom"
                                    variant="outline"
                                    size="icon"
                                    @click="updateScale(Math.max(2, scaleFactor - 1))"
                                    class="!p-1"
                                    :disabled="scaleFactor <= 2"
                                >
                                    <Minus class="h-4 w-4" />
                                </Button>
                                <span class="w-8 text-center text-sm font-medium text-gray-700 dark:text-gray-300">{{ scaleFactor }}x</span>
                                <Button
                                    type="buttom"
                                    variant="outline"
                                    size="icon"
                                    @click="updateScale(Math.min(10, scaleFactor + 1))"
                                    class="!p-1"
                                    :disabled="scaleFactor >= 10"
                                >
                                    <Plus class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Toggle Grid -->
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            @click="showGrid = !showGrid"
                            class="ml-4 !p-1"
                            :class="{ 'bg-gray-100 dark:bg-gray-700': showGrid }"
                        >
                            <Grid class="h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Botões agrupados -->
                    <div class="flex items-center space-x-3">
                        <!-- Botão para abrir o drawer de produtos -->
                        <Button variant="primary" size="icon" @click="showProductDrawer = true">
                            <Package class="h-6 w-6" />
                        </Button>
                        <Button type="button" variant="secondary" v-if="sections.length > 0" @click="invertOrder" class="flex items-center">
                            <ArrowLeftRight class="mr-1 h-4 w-4" />
                            <span class="hidden md:block">Inverter Ordem</span>
                        </Button>

                        <Button type="button" variant="secondary" @click="showNewPlanogramModal = true" class="flex items-center">
                            <Plus class="mr-1 h-4 w-4" />
                            <span class="hidden md:block">Adicionar Planograma</span>
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de Visualização -->
        <div class="mt-2 border-b-2 border-gray-300 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-800">
            <div
                :style="containerStyle"
                class="mx-auto flex min-h-screen items-center justify-center border-2 border-gray-300 p-1 dark:border-gray-700"
            >
                <MovableContainer :storage-id="'gondola-' + record.id">
                    <SectionList
                        v-model:sections="sections"
                        :scale-factor="scaleFactor"
                        :base-height="Number(record.base_height)"
                        @remove="handleSectionRemove"
                        @add="showNewPlanogramModal = true"
                        :gondola="record"
                        shelf-direction="bottom"
                        @select-shelf="handleShelfSelect"
                        @update:layer="updateLayer"
                        @update:segment="updateSegment"
                    />
                </MovableContainer>
            </div>
        </div>

        <!-- Modal de adicionar planograma -->
        <AddPlanogramModal v-model:open="showNewPlanogramModal" :gondola="record" @save="handleAddSection" @cancel="showNewPlanogramModal = false" />

        <!-- Slide-over de produtos -->
        <ProductDrawer v-model:open="showProductDrawer" @select-product="handleProductSelect" />

        <DragDebug />
    </div>
</template>

<script setup>
import { Button } from '@/components/ui/button';
import { SmallInput } from '@/components/ui/input';
import { router } from '@inertiajs/vue3';
import { ArrowLeftRight, Grid, Minus, Package, Plus } from 'lucide-vue-next';
import { computed, ref, toRaw, watch } from 'vue';
import AddPlanogramModal from './AddPlanogramModal.vue';
import MovableContainer from './MovableContainer.vue';
import ProductDrawer from './ProductDrawer.vue';
import SectionList from './SectionList.vue';
import   DragDebug   from './DragDebug.vue';

// Props do componente
const props = defineProps({
    record: {
        type: Object,
        required: true,
    },
    sections: {
        type: Array,
        default: () => [],
    },
});

// =====================================
// Estado do componente
// =====================================
const sections = ref(JSON.parse(JSON.stringify(props.record.sections || [])));
const scaleFactor = ref(props.record.scale_factor);
const showGrid = ref(true);
const showNewPlanogramModal = ref(false);
const showProductDrawer = ref(false);
const selectedShelf = ref(null);

// =====================================
// Watchers
// =====================================

/**
 * Observa mudanças nas seções vindas do servidor
 */
watch(
    () => props.record.sections,
    (newSections) => {
        if (newSections && !compareArrays(toRaw(sections.value), toRaw(newSections))) {
            sections.value = JSON.parse(JSON.stringify(newSections));
        }
    },
    { deep: true },
);

// =====================================
// Computed Properties
// =====================================

/**
 * Estilo do container principal, inclui a grade de fundo
 */
const containerStyle = computed(() => {
    const grid = calculateWidth(10); // Grid de 10cm
    return {
        position: 'relative',
        backgroundImage: showGrid.value
            ? 'linear-gradient(to right, rgba(75, 85, 99, 0.3) 1px, transparent 1px), linear-gradient(to bottom, rgba(75, 85, 99, 0.3) 1px, transparent 1px)'
            : 'none',
        backgroundSize: showGrid.value ? `${grid}px ${grid}px` : 'auto',
        width: `100%`,
    };
});

// =====================================
// Funções auxiliares
// =====================================

/**
 * Compara arrays de seções sem acionar reatividade
 * @param {Array} arr1 - Primeiro array
 * @param {Array} arr2 - Segundo array
 * @returns {Boolean} - Se os arrays são equivalentes
 */
function compareArrays(arr1, arr2) {
    if (!arr1 || !arr2) return false;
    if (arr1.length !== arr2.length) return false;

    // Comparação simples por ID
    const ids1 = arr1
        .map((s) => s.id)
        .sort()
        .join(',');
    const ids2 = arr2
        .map((s) => s.id)
        .sort()
        .join(',');
    return ids1 === ids2;
}

/**
 * Calcula a largura em pixels baseada em centímetros
 * @param {Number} width - Largura em centímetros
 * @returns {Number} - Largura em pixels
 */
function calculateWidth(width) {
    return width * scaleFactor.value;
}

// =====================================
// Funções de controle de UI
// =====================================

/**
 * Atualiza o fator de escala da visualização
 * @param {Number} value - Novo valor de escala (entre 2 e 10)
 */
function updateScale(value) {
    scaleFactor.value = value;
    window.axios.put(
        route('gondolas.updateScaleFactor', {
            gondola: props.record.id,
        }),
        {
            scale_factor: value,
        },
    );
}

/**
 * Adiciona uma nova seção via API
 * @param {Object} section - Dados da nova seção
 */
const handleAddSection = (section) => {
    // Fecha o modal imediatamente
    showNewPlanogramModal.value = false;

    // Postar a nova seção para a API
    router.post(
        route('sections.store', {
            gondola: props.record.id,
        }),
        {
            ...section,
            gondola_id: props.record.id,
        },
        {
            preserveScroll: true,
            onError: (errors) => {
                console.error('Erro ao adicionar seção:', errors);
            },
        },
    );
};

/**
 * Remove uma seção da gôndola
 * @param {Number} sectionId - ID da seção a ser removida
 */
const handleSectionRemove = (sectionId) => {
    router.delete(route('sections.destroy', { section: sectionId }), {
        preserveScroll: true,
        onSuccess: () => {
            // Atualiza o estado local removendo a seção
            const updatedSections = sections.value.filter((section) => section.id !== sectionId);
            sections.value = updatedSections;
        },
        onError: (errors) => {
            console.error('Erro ao excluir seção:', errors);
            router.reload();
        },
    });
};

/**
 * Inverte a ordem das seções da gôndola
 */
const invertOrder = () => {
    const newOrder = [...sections.value].reverse();
    sections.value = newOrder;

    // Atualizar posições no servidor
    const updates = newOrder.map((section, index) => ({
        id: section.id,
        ordering: index,
    }));

    router.put(
        route('sections.reorder', {
            gondola: props.record.id,
        }),
        { sections: updates },
    );
};

/**
 * Manipula a seleção de prateleira para adicionar produtos
 * @param {Object} shelf - Prateleira selecionada
 */
function handleShelfSelect(shelf) {
    selectedShelf.value = shelf;
    showProductDrawer.value = true;
}

/**
 * Adiciona um produto à prateleira selecionada
 * @param {Object} product - Produto a ser adicionado
 */
function handleProductSelect(product) {
    if (!selectedShelf.value) return;

    router.post(
        route('shelf-products.store'),
        {
            shelf_id: selectedShelf.value.id,
            product_id: product.id,
            quantity: 1, // Quantidade padrão
        },
        {
            preserveScroll: true,
            onError: (errors) => {
                console.error('Erro ao adicionar produto à prateleira:', errors);
            },
        },
    );
}

/**
 * Função para registrar atualização de layer
 * @param {Object} layer - Layer atualizado
 */
const updateLayer = (layer) => {
    console.log('Quantidade de layer atualizada com sucesso');
};

/**
 * Função para registrar atualização de segmento
 * @param {Object} segment - Segmento atualizado
 */
const updateSegment = (segment) => {
    console.log('Quantidade de segmento atualizada com sucesso');
};
</script>
