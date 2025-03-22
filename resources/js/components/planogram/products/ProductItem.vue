<template>
    <li class="px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-move"
        @click="$emit('click', product)"
        draggable="true"
        @dragstart="onDragStart"
        :data-product-id="product.id">
        <div class="flex items-center space-x-4">
            <div
                class="flex-shrink-0 h-16 w-16 bg-gray-100 dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700 p-1 flex items-center justify-center">
                <img v-if="product.image_url" :src="product.image_url"
                    :alt="product.name" class="max-h-full max-w-full object-contain" />
                <Package v-else class="h-8 w-8 text-gray-400" />
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ product.name }}
                </p>
                <div class="flex items-center text-xs text-gray-500 mt-1">
                    <span>{{ product.width }}×{{ product.height }}×{{ product.depth }}
                        cm</span>
                    <span v-if="product.sku"
                        class="ml-2 px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">
                        SKU: {{ product.sku }}
                    </span>
                </div>
            </div>

            <div class="flex items-center">
                <Button size="sm" variant="ghost" @click.stop="$emit('view-stats')">
                    <BarChart2 class="h-5 w-5 mr-1" />
                    <span>Ver estatísticas</span>
                </Button>
            </div>
        </div>
    </li>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';
import { Package, BarChart2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps({
    product: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['click', 'drag-start', 'view-stats']);

function onDragStart(event) {
    emit('drag-start', event, props.product);
}
</script>