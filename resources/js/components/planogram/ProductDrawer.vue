<template>
    <div>
        <!-- Slide-over panel for product selection -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none z-[100]" v-if="isOpen">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <!-- Slide-over panel - pointer-events-auto apenas no painel -->
                    <div class="pointer-events-auto w-screen max-w-md transform transition ease-in-out duration-500 sm:duration-700"
                        :class="{ 'translate-x-0': isOpen, 'translate-x-full': !isOpen }">
                        <div class="flex h-full flex-col overflow-hidden bg-white dark:bg-gray-900 shadow-xl">
                            <!-- Header -->
                            <div class="bg-primary-700 dark:bg-primary-900 py-3 px-4">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-lg font-medium text-white">Produtos</h2>
                                    <button type="button"
                                        class="rounded-md bg-primary-700 dark:bg-primary-900 text-white hover:text-gray-200 focus:outline-none"
                                        @click="isOpen = false">
                                        <X class="h-6 w-6" />
                                    </button>
                                </div>
                                <p class="mt-1 text-sm text-primary-200">
                                    Selecione os produtos para adicionar à prateleira
                                </p>
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
                            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 sm:px-6">
                                <div class="flex items-center justify-end space-x-3">
                                    <Button variant="outline" @click="isOpen = false">Fechar</Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import the ProductStatsModal component -->
        <ProductStatsModal 
            v-model:open="showProductStatsModal" 
            :product="selectedProduct" 
            @select="handleProductStatSelect" 
        />
    </div>
</template>

<script setup>
import { ref, computed, defineProps, defineEmits, watch, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { X } from 'lucide-vue-next';
import ProductFilters from './products/ProductFilters.vue';
import ProductList from './products/ProductList.vue';
import ProductStatsModal from './products/ProductStatsModal.vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false
    },
    position: {
        type: String,
        default: 'right'
    },
});

const emits = defineEmits(['update:open', 'select-product']);

// Local state
const isOpen = ref(props.open);
const loading = ref(false);
const products = ref([]);
const categories = ref([]);
const showProductStatsModal = ref(false);
const selectedProduct = ref(null);

const filters = ref({
    hangable: false,
    stackable: false,
    flammable: false,
    perishable: false,
    search: '',
    category: null
});

// Computed property to count active filters
const activeFiltersCount = computed(() => {
    let count = 0;

    // Count selected category
    if (filters.value.category) {
        count++;
    }

    // Count enabled checkboxes
    Object.values(filters.value).forEach(value => {
        if (value && typeof value === "boolean") count++;
    });

    return count;
});

// Watch for external changes to open prop
watch(() => props.open, (newVal) => {
    isOpen.value = newVal;
});

watch(filters, () => {
    fetchProducts();
}, { deep: true });

// Watch for internal changes to isOpen
watch(isOpen, (newVal) => {
    emits('update:open', newVal);
});

// Filtered products based on search and filters
const filteredProducts = computed(() => products.value);

// Clear all filters
function clearFilters() {
    filters.value = {
        hangable: false,
        stackable: false,
        flammable: false,
        perishable: false,
        search: '',
        category: null
    };
}

// Select product and emit event
function selectProduct(product) {
    emits('select-product', product);
}

// Function to open product statistics modal
function openProductStats(product) {
    selectedProduct.value = product;
    showProductStatsModal.value = true;
}

// Handle product selection from stats modal
function handleProductStatSelect(product) {
    selectProduct(product);
}

// Fetch products and categories when component mounts
onMounted(async () => {
    await fetchProducts();
    await fetchCategories();
});

// Funções para carregar produtos e categorias
async function fetchProducts() {
    try {
        loading.value = true;
        const { data } = await window.axios.get(route('api.products.index'), {
            params: {
                search: filters.value.search,
                hangable: filters.value.hangable,
                stackable: filters.value.stackable,
                flammable: filters.value.flammable,
                perishable: filters.value.perishable,
                category: filters.value.category
            }
        });
        products.value = data;
    } catch (error) {
        console.error('Erro ao carregar produtos:', error);
    } finally {
        loading.value = false;
    }
}

async function fetchCategories() {
    try {
        const { data } = await window.axios.get(route('api.categories.index'));
        categories.value = data;
    } catch (error) {
        console.error('Erro ao carregar categorias:', error);
    }
}

// Handle drag start event
function handleDragStart(event, product) {
    // Set data transfer with product info
    event.dataTransfer.setData('application/json', JSON.stringify(product));
    event.dataTransfer.effectAllowed = 'copy';
    
    // Create a custom drag image for better visual feedback
    const dragImage = document.createElement('div');
    dragImage.innerHTML = `
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-md p-2 flex items-center">
            <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center mr-2">
                ${product.image_url ? 
                    `<img src="${product.image_url}" alt="${product.name}" class="max-h-full max-w-full object-contain" />` : 
                    '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>'}
            </div>
            <span class="text-sm font-medium truncate max-w-[100px]">${product.name}</span>
        </div>
    `;
    
    dragImage.style.position = 'absolute';
    dragImage.style.top = '-1000px';
    document.body.appendChild(dragImage);
    
    // Set the custom drag image
    event.dataTransfer.setDragImage(dragImage, 25, 25);
    
    // Remove the element after the drag starts
    setTimeout(() => {
        document.body.removeChild(dragImage);
    }, 0);
}
</script>