<template>
    <div>
        <!-- Slide-over panel for product selection -->
        <div class="pointer-events-none fixed inset-0 z-[100] overflow-hidden" v-if="isOpen">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <!-- Slide-over panel -->
                    <div
                        class="pointer-events-auto w-screen max-w-md transform transition duration-500 ease-in-out sm:duration-700"
                        :class="{ 'translate-x-0': isOpen, 'translate-x-full': !isOpen }"
                    >
                        <div class="flex h-full flex-col overflow-hidden bg-white shadow-xl dark:bg-gray-900">
                            <!-- Header -->
                            <div class="bg-primary-700 dark:bg-primary-900 px-4 py-3">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-lg font-medium text-white">Produtos</h2>
                                    <button
                                        type="button"
                                        class="bg-primary-700 dark:bg-primary-900 rounded-md text-white hover:text-gray-200 focus:outline-none"
                                        @click="closeDrawer"
                                    >
                                        <X class="h-6 w-6" />
                                    </button>
                                </div>
                                <p class="text-primary-200 mt-1 text-sm">Selecione os produtos para adicionar à prateleira</p>
                            </div>

                            <!-- Filters component -->
                            <ProductFilters
                                v-model:filters="filters"
                                :categories="categories"
                                :active-filters-count="activeFiltersCount"
                                @clear="clearFilters"
                            />

                            <!-- Product list -->
                            <ProductList
                                :products="filteredProducts"
                                :loading="loading"
                                @select-product="selectProduct"
                                @open-stats="openProductStats"
                                @drag-start="handleDragStart"
                            />

                            <!-- Footer -->
                            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700 sm:px-6">
                                <div class="flex items-center justify-end space-x-3">
                                    <Button variant="outline" @click="closeDrawer">Fechar</Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Stats Modal -->
        <ProductStatsModal v-model:open="showProductStatsModal" :product="selectedProduct" @select="handleProductStatSelect" />
    </div>
</template>

<script setup>
import { Button } from '@/components/ui/button';
import { X } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import ProductFilters from './products/ProductFilters.vue';
import ProductList from './products/ProductList.vue';
import ProductStatsModal from './products/ProductStatsModal.vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    position: {
        type: String,
        default: 'right',
    },
});

const emits = defineEmits(['update:open', 'select-product']);

// Estado local
const isOpen = ref(props.open);
const loading = ref(false);
const products = ref([]);
const categories = ref([]);
const showProductStatsModal = ref(false);
const selectedProduct = ref(null);

// Estado dos filtros com valores iniciais
const filters = ref({
    hangable: false,
    stackable: false,
    flammable: false,
    perishable: false,
    search: '',
    category: null,
});

// Contagem de filtros ativos
const activeFiltersCount = computed(() => {
    let count = 0;

    // Categoria selecionada
    if (filters.value.category !== null) {
        count++;
    }

    // Checkboxes ativados
    if (filters.value.hangable) count++;
    if (filters.value.stackable) count++;
    if (filters.value.flammable) count++;
    if (filters.value.perishable) count++;

    // Campo de busca
    if (filters.value.search && filters.value.search.trim() !== '') {
        count++;
    }

    return count;
});

// Observa mudanças na prop open
watch(
    () => props.open,
    (newVal) => {
        isOpen.value = newVal;
        if (newVal && products.value.length === 0) {
            // Carrega dados apenas se o drawer for aberto e não houver produtos
            fetchProducts();
            fetchCategories();
        }
    },
);

// Observa os filtros para refazer a busca
watch(
    filters,
    () => {
        fetchProducts();
    },
    { deep: true },
);

// Sincroniza o estado local com o componente pai
watch(isOpen, (newVal) => {
    emits('update:open', newVal);
});

// Produtos filtrados
const filteredProducts = computed(() => products.value);

/**
 * Limpa todos os filtros
 */
function clearFilters() {
    filters.value = {
        hangable: false,
        stackable: false,
        flammable: false,
        perishable: false,
        search: '',
        category: null,
    };
}

/**
 * Fecha o drawer
 */
function closeDrawer() {
    isOpen.value = false;
}

/**
 * Seleciona um produto e emite o evento
 */
function selectProduct(product) {
    emits('select-product', product);
}

/**
 * Abre o modal de estatísticas do produto
 */
function openProductStats(product) {
    selectedProduct.value = product;
    showProductStatsModal.value = true;
}

/**
 * Trata a seleção de produto a partir do modal de estatísticas
 */
function handleProductStatSelect(product) {
    selectProduct(product);
}

/**
 * Inicializa o componente
 */
onMounted(() => {
    if (isOpen.value) {
        fetchProducts();
        fetchCategories();
    }
});

/**
 * Busca produtos com os filtros aplicados
 */
async function fetchProducts() {
    try {
        loading.value = true;

        // Preparar parâmetros de filtro para a API
        const params = {};

        if (filters.value.search) {
            params.search = filters.value.search;
        }

        if (filters.value.category !== null) {
            params.category = filters.value.category;
        }

        // Adiciona filtros booleanos apenas se estiverem ativos
        if (filters.value.hangable) params.hangable = true;
        if (filters.value.stackable) params.stackable = true;
        if (filters.value.flammable) params.flammable = true;
        if (filters.value.perishable) params.perishable = true;

        const { data } = await window.axios.get(route('api.products.index'), { params });
        products.value = data; 
    } catch (error) {
        console.error('Erro ao carregar produtos:', error);
    } finally {
        loading.value = false;
    }
}

/**
 * Busca categorias disponíveis
 */
async function fetchCategories() {
    try {
        const { data } = await window.axios.get(route('api.categories.index'));
        categories.value = data; 
    } catch (error) {
        console.error('Erro ao carregar categorias:', error);
    }
}

/**
 * Trata o início do arrasto de um produto
 */
function handleDragStart(event, product) {
    // Configura dados de transferência
    event.dataTransfer.setData('application/json', JSON.stringify(product));
    event.dataTransfer.effectAllowed = 'copy';

    // Cria imagem personalizada para o arrasto
    const dragImage = document.createElement('div');
    dragImage.innerHTML = `
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-md p-2 flex items-center">
            <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center mr-2">
                ${
                    product.image_url
                        ? `<img src="${product.image_url}" alt="${product.name}" class="max-h-full max-w-full object-contain" />`
                        : '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>'
                }
            </div>
            <span class="text-sm font-medium truncate max-w-[100px]">${product.name}</span>
        </div>
    `;

    // Configura a imagem de arrasto
    dragImage.style.position = 'absolute';
    dragImage.style.top = '-1000px';
    document.body.appendChild(dragImage);
    event.dataTransfer.setDragImage(dragImage, 25, 25);

    // Remove o elemento temporário
    setTimeout(() => {
        document.body.removeChild(dragImage);
    }, 0);
}
</script>
