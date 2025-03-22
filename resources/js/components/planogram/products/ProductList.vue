<template>
    <div class="flex-1 overflow-y-auto">
        <div class="px-4 py-2 sticky top-0 bg-white dark:bg-gray-900 z-10 border-b border-gray-200 dark:border-gray-800">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ products.length }} produtos encontrados
            </h3>
        </div>

        <div v-if="loading" class="flex justify-center items-center py-12">
            <Loader class="w-8 h-8 animate-spin text-primary-500" />
            <span class="ml-3 text-sm text-gray-500">Carregando produtos...</span>
        </div>

        <div v-else-if="products.length === 0" class="py-12 text-center">
            <Package class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-200">
                Nenhum produto encontrado
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Tente ajustar seus filtros para encontrar o que procura.
            </p>
        </div>

        <ul v-else role="list" class="divide-y divide-gray-200 dark:divide-gray-800">
            <ProductItem 
                v-for="product in products" 
                :key="product.id" 
                :product="product"
                @click="selectProduct(product)"
                @drag-start="handleDragStart"
                @view-stats="openProductStats(product)"
            />
        </ul>
    </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';
import { Loader, Package } from 'lucide-vue-next';
import ProductItem from './ProductItem.vue';

const props = defineProps({
    products: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['select-product', 'open-stats', 'drag-start']);

function selectProduct(product) {
    emit('select-product', product);
}

function openProductStats(product) {
    emit('open-stats', product);
}

function handleDragStart(event, product) {
    emit('drag-start', event, product);
}
</script>