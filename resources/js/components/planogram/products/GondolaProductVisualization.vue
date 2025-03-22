<template>
    <div class="product-visualization" :style="containerStyle">
      <div 
        v-for="(item, index) in productItems" 
        :key="index"
        class="product-item"
        :style="getProductItemStyle(index)"
      >
        <img 
          v-if="product.image_url" 
          :src="product.image_url" 
          :alt="product.name" 
          class="product-image"
        />
        <div v-else class="product-placeholder">
          <Package :size="iconSize" class="text-gray-400" />
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { computed } from 'vue';
  import { Package } from 'lucide-vue-next';
  
  const props = defineProps({
    product: {
      type: Object,
      required: true
    },
    quantity: {
      type: Number,
      default: 1
    },
    shelfWidth: {
      type: Number,
      required: true
    },
    shelfHeight: {
      type: Number,
      required: true
    },
    scaleFactor: {
      type: Number,
      default: 1
    }
  });
  
  // Lista de produtos baseada na quantidade
  const productItems = computed(() => {
    return Array.from({ length: props.quantity }, (_, i) => i);
  });
  
  // Estilo do container
  const containerStyle = computed(() => {
    return {
        left: '0',
        bottom: '0',
      width: `${props.shelfWidth * props.scaleFactor}px`,
      height: `${props.shelfHeight * props.scaleFactor}px`,
    };
  });
  
  // Tamanho do ícone baseado na altura da prateleira
  const iconSize = computed(() => {
    const baseSize = 16;
    const scaledSize = baseSize * props.scaleFactor;
    return Math.max(12, Math.min(24, scaledSize));
  });
  
  // Define o posicionamento de cada produto na prateleira
  function getProductItemStyle(index: number) {
    // Se houver apenas um produto, centralize-o
    
  
    // Caso contrário, distribua os produtos uniformemente
    const productWidth = props.product.width || 10; // Largura padrão se não existir
    const totalProductsWidth = productWidth * props.quantity;
    const spaceBetween = 1; // 1cm entre produtos
    const totalGaps = props.quantity - 1;
    const totalSpaceNeeded = totalProductsWidth + (totalGaps * spaceBetween);
    
    let startX = 0;
    
    // Se o espaço total necessário for menor que a largura da prateleira,
    // centralize o grupo de produtos
    if (totalSpaceNeeded < props.shelfWidth) {
      startX = (props.shelfWidth - totalSpaceNeeded) / 2;
    }
    
    // Posição X para este produto específico
    const posX = startX + (index * (productWidth + spaceBetween));
    
    // Retorna o estilo
    return {
      left: `${posX * props.scaleFactor}px`,
      maxWidth: `${productWidth * props.scaleFactor}px`,
      maxHeight: `${props.shelfHeight * 0.9 * props.scaleFactor}px`, // 90% da altura da prateleira
    };
  }
  </script>
  
  <style scoped>
  .product-visualization {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
  }
  
  .product-item {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .product-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
  }
  
  .product-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    width: 100%;
    height: 100%;
    min-width: 20px;
    min-height: 20px;
  }
  </style>