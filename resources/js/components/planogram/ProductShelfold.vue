<template>
  <div class="shelf-products-container">
    <!-- Products on shelf -->
    <TransitionGroup
      name="product"
      tag="div"
      class="products-layout"
      :style="containerStyle"
    >
      <div 
        v-for="productItem in displayProducts" 
        :key="productItem.id"
        class="product-item"
        :style="getProductStyle(productItem)"
        :class="{ 'selected': selectedProduct && selectedProduct.id === productItem.id }"
        @click.stop="selectProduct(productItem)"
      >
        <div class="product-preview" :style="getPreviewStyle(productItem.product)">
          <img 
            v-if="productItem.product.image_url" 
            :src="productItem.product.image_url" 
            :alt="productItem.product.name"
            class="product-image"
          />
          <div v-else class="product-placeholder">
            <Package class="w-full h-full text-gray-400" />
          </div>
          
          <div class="product-info">
            <div class="product-name">{{ productItem.product.name }}</div>
            <div class="product-quantity">×{{ productItem.quantity }}</div>
          </div>
        </div>
        
        <Button 
          size="icon" 
          variant="destructive" 
          class="remove-button"
          @click.stop="removeProduct(productItem)"
        >
          <X class="h-3 w-3" />
        </Button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { ref, computed, defineProps, defineEmits, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Package, X } from 'lucide-vue-next';

const props = defineProps({
  products: {
    type: Array,
    default: () => []
  },
  shelf: {
    type: Object,
    required: true
  },
  scaleFactor: {
    type: Number,
    default: 2
  }
});

const emits = defineEmits(['remove', 'select']);

const selectedProduct = ref(null);

// Computed products to display
const displayProducts = computed(() => {
  return props.products.map(product => {
    // Enrich product data with positioning info if not already present
    if (!product.position_x && !product.position_y) {
      return {
        ...product,
        position_x: Math.floor(Math.random() * (props.shelf.width - product.product.width)),
        position_y: -product.product.height // Position above the shelf
      }
    }
    return product;
  });
});

// Container style based on shelf dimensions
const containerStyle = computed(() => {
  const { width, height, depth } = props.shelf;
  
  return {
    width: `${width * props.scaleFactor}px`,
    height: `${height * props.scaleFactor}px`,
    position: 'absolute',
    top: 0,
    left: 0,
    pointerEvents: 'none', // Let shelf handle drag events
    zIndex: 2
  };
});

// Get style for a product item based on its position and dimensions
function getProductStyle(productItem) {
  const { position_x, position_y, product } = productItem;
  const { width, height, depth } = product;
  
  return {
    position: 'absolute',
    width: `${width * props.scaleFactor}px`,
    height: `${height * props.scaleFactor}px`,
    left: `${position_x * props.scaleFactor}px`,
    top: `${position_y * props.scaleFactor}px`,
    zIndex: 5,
    pointerEvents: 'auto' // Make products interactive
  };
}

// Get style for product preview visualization
function getPreviewStyle(product) {
  const ratio = Math.min(
    1,
    (props.shelf.width * 0.8) / product.width,
    (props.shelf.height * 0.8) / product.height
  );
  
  return {
    width: '100%',
    height: '100%',
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
    background: 'rgba(255,255,255,0.9)',
    boxShadow: '0 2px 6px rgba(0,0,0,0.1)',
    borderRadius: '4px',
    overflow: 'hidden',
    padding: '4px'
  };
}

// Select a product
function selectProduct(productItem) {
  selectedProduct.value = productItem;
  emits('select', productItem);
}

// Remove product from shelf
function removeProduct(productItem) {
  emits('remove', productItem);
}
</script>

<style scoped>
.products-layout {
  position: relative;
}

.product-item {
  background: transparent;
  cursor: move;
  transition: transform 0.2s, box-shadow 0.2s;
}

.product-item.selected {
  outline: 2px solid #3b82f6;
  z-index: 10;
}

.product-item:hover {
  z-index: 10;
}

.product-preview {
  width: 100%;
  height: 100%;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.product-image {
  max-width: 100%;
  max-height: 70%;
  object-fit: contain;
}

.product-placeholder {
  width: 50%;
  height: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.product-info {
  width: 100%;
  padding: 2px 4px;
  font-size: 0.6rem;
  text-align: center;
  background: rgba(0,0,0,0.05);
  margin-top: auto;
}

.product-name {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 500;
}

.product-quantity {
  font-size: 0.55rem;
  color: #666;
}

.remove-button {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 16px;
  height: 16px;
  padding: 0;
  min-width: 0;
  opacity: 0;
  transition: opacity 0.2s;
}

.product-item:hover .remove-button {
  opacity: 1;
}

/* Transition animations */
.product-move {
  transition: transform 0.5s;
}

.product-enter-active,
.product-leave-active {
  transition: all 0.3s ease;
}

.product-enter-from {
  opacity: 0;
  transform: translateY(-20px);
}

.product-leave-to {
  opacity: 0;
  transform: scale(0.8);
}
</style>
