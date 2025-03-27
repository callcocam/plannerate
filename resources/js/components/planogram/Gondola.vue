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
                        <!-- Botão flutuante para abrir o drawer de produtos -->
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
                <!-- Use o wrapper com alça de arrasto aqui -->
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

        <!-- Botão flutuante para abrir o drawer de produtos -->
        <Button
            variant="primary"
            class="fixed bottom-6 right-6 flex h-12 w-12 items-center justify-center rounded-full shadow-lg"
            @click="showProductDrawer = true"
        >
            <Package class="h-6 w-6" />
        </Button>
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
import DragDebug from './DragDebug.vue';
const props = defineProps({
    record: {
        type: Object,
    },
    sections: {
        type: Array,
    },
});

// Use toRaw to break reactivity chain when initializing
const sections = ref(JSON.parse(JSON.stringify(props.record.sections || [])));

// Watch for changes in props.record.sections (if they come from server)
watch(
    () => props.record.sections,
    (newSections) => {
        if (newSections && !compareArrays(toRaw(sections.value), toRaw(newSections))) {
            sections.value = JSON.parse(JSON.stringify(newSections));
        }
    },
    { deep: true },
);

// Helper function to compare arrays without triggering reactivity
function compareArrays(arr1, arr2) {
    if (!arr1 || !arr2) return false;
    if (arr1.length !== arr2.length) return false;

    // Simple comparison by id
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
 * @param width - Largura em centímetros
 * @returns Largura em pixels
 */
function calculateWidth(width) {
    return width * scaleFactor.value;
}
// =====================================
// Computed Properties
// =====================================

/**
 * Estilo do container principal, inclui a grade de fundo
 */
const containerStyle = computed(() => {
    const grid = calculateWidth(10); // Grid de 10cm
    const { record } = props;
    return {
        position: 'relative',
        backgroundImage: showGrid.value
            ? 'linear-gradient(to right, rgba(75, 85, 99, 0.3) 1px, transparent 1px), linear-gradient(to bottom, rgba(75, 85, 99, 0.3) 1px, transparent 1px)'
            : 'none',
        backgroundSize: showGrid.value ? `${grid}px ${grid}px` : 'auto',
        width: `100%`,
    };
});
const scaleFactor = ref(props.record.scale_factor);
const showGrid = ref(true);

// =====================================
// Funções de UI
// =====================================

/**
 * Atualiza o fator de escala da visualização
 * @param value - Novo valor de escala (entre 1 e 10)
 */
function updateScale(value) {
    scaleFactor.value = value;
    const { record } = props;
    window.axios.put(
        route('gondolas.updateScaleFactor', {
            gondola: record.id,
        }),
        {
            scale_factor: value,
        },
    );
}
const showNewPlanogramModal = ref(false);

// Adiciona uma nova seção
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
            onSuccess: (page) => {
                // Fazer alguma coisa com a resposta, se necessário
            },
            onError: (errors) => {
                console.error('Erro ao adicionar seção:', errors);
                // Remove the temporary section in case of error
            },
        },
    );
};

const handleSectionRemove = (sectionId) => {
    // Then send request to server
    router.delete(route('sections.destroy', { section: sectionId }), {
        preserveScroll: true,
        onSuccess: () => {
            // First update local state to remove the section
            const updatedSections = sections.value.filter((section) => section.id !== sectionId);
            sections.value = updatedSections;
        },
        onError: (errors) => {
            console.error('Erro ao excluir seção:', errors);
            // If error occurs, we should reload to get the correct state
            router.reload();
        },
    });
};

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

const showProductDrawer = ref(false);
const selectedShelf = ref(null);

// Handle shelf selection
function handleShelfSelect(shelf) {
    selectedShelf.value = shelf;
    showProductDrawer.value = true;
}

// Handle product selection
function handleProductSelect(product) {
    if (!selectedShelf.value) return;

    // Add product to selected shelf
    router.post(
        route('shelf-products.store'),
        {
            shelf_id: selectedShelf.value.id,
            product_id: product.id,
            quantity: 1, // Default quantity
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                // You might want to update the shelf's products here
                // or load them from the server
            },
            onError: (errors) => {
                console.error('Erro ao adicionar produto à prateleira:', errors);
            },
        },
    );

    // Optionally close the drawer after selection
    // showProductDrawer.value = false;
}

// Função para atualizar a quantidade de layer no componente pai
const updateLayer = (layer) => {
    // Emitir evento para atualizar a prateleira no componente pai
    console.log('Quantidade de layer atualizada com sucesso');
};

// Função para atualizar o segmento no componente pai
const updateSegment = (segment) => {
    // Emitir evento para atualizar o segmento no componente pai
    console.log('Quantidade de segmento atualizada com sucesso');
};
</script>
