<template>
    <div class="product-view" :style="productStyle">
        <div class="product-image">
            <img v-if="productImageUrl" :src="productImageUrl" alt="Product" style="padding: 0 !important; margin: 0 !important" />
            <div v-else class="product-placeholder">
                {{ product.name }}
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Layer, Product, Shelf } from './planogram';

const props = defineProps<{
    product: Product & {
        image_url?: string; 
    };
    layer: Layer;
    shelf: Shelf;
    scaleFactor: number;
}>();

// Compute the image URL from the product
const productImageUrl = computed(() => {
    if (props.product?.image_url) {
        return props.product.image_url;
    }
    return null;
});

// Verifica o modo de distribuição da prateleira
const distributionMode = computed(() => {
    // Verificar se a prateleira tem configurações
    const shelfSettings = props.shelf.settings
        ? typeof props.shelf.settings === 'string'
            ? JSON.parse(props.shelf.settings)
            : props.shelf.settings
        : {};

    // Retorna o modo de distribuição ou 'equal' como padrão
    return shelfSettings.distributionMode || 'equal';
});

// Calculate product dimensions based on scale factor
const productStyle = computed(() => {
    const width = props.product.width * props.scaleFactor;
    const height = props.product.height * props.scaleFactor;

    // Estilo base
    const style = {
        width: `${width}px`,
        height: `${height}px`,
    };

    // Aplicar estilo para distribuição mesmo quando há apenas um segmento
    if (props.shelf.segments) {
        // Adiciona uma margem automática para centralizar o produto no espaço alocado
        if (distributionMode.value === 'equal' || distributionMode.value === 'proportional') {
            return {
                ...style,
                margin: 'auto', // Centraliza o produto dentro do espaço disponível
            };
        }
    }

    return style;
});
</script>

<style scoped>
.product-view {
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
    position: relative;
}

.product-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
}

.product-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
</style>
