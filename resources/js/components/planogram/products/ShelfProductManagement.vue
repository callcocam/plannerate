<template>
    <div class="product-settings-panel">
      <div v-if="shelf.product" class="p-4 bg-white dark:bg-gray-800 rounded-md shadow-lg">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0 h-12 w-12 bg-gray-100 dark:bg-gray-700 rounded-md mr-3 flex items-center justify-center">
            <img v-if="shelf.product.image_url" :src="shelf.product.image_url"
                 :alt="shelf.product.name" class="max-h-full max-w-full object-contain" />
            <Package v-else class="h-6 w-6 text-gray-400" />
          </div>
          <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ shelf.product.name }}</h3>
            <p class="text-xs text-gray-500">
              {{ shelf.product.width }}×{{ shelf.product.height }}×{{ shelf.product.depth }} cm
            </p>
          </div>
        </div>
  
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Quantidade de produtos
          </label>
          <div class="flex items-center">
            <Button 
              size="sm" 
              variant="outline" 
              @click="updateQuantity(Math.max(1, Number(shelf.quantity || 1) - 1))"
              :disabled="Number(shelf.quantity || 1) <= 1"
            >
              <Minus class="h-4 w-4" />
            </Button>
            <SmallInput 
              type="text" 
              class="w-20 mx-2 text-center" 
              v-model="quantity" 
              min="1" 
              :max="maxProductsPerShelf"
              @change="validateAndUpdateQuantity"
            />
            <Button 
              size="sm" 
              variant="outline" 
              @click="updateQuantity(Math.min(maxProductsPerShelf, Number(shelf.quantity || 1) + 1))"
              :disabled="Number(shelf.quantity || 1) >= maxProductsPerShelf"
            >
              <Plus class="h-4 w-4" />
            </Button>
          </div>
          <p class="text-xs text-gray-500 mt-1">
            Máximo de {{ maxProductsPerShelf }} produtos nesta prateleira
          </p>
        </div> 
      </div>
      <div v-else class="p-4 bg-white dark:bg-gray-800 rounded-md shadow-lg text-center">
        <p class="text-sm text-gray-500">
          Nenhum produto atribuído a esta prateleira.
        </p>
        <p class="text-sm text-gray-500 mt-2">
          Arraste um produto do painel lateral e solte aqui.
        </p>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, watch, computed } from 'vue';
  import { Button } from '@/components/ui/button';
  import { Input, SmallInput } from '@/components/ui/input';
  import { Package, Minus, Plus, Trash2 } from 'lucide-vue-next';
  
  const props = defineProps({
    shelf: {
      type: Object,
      required: true
    },
    // Para cálculos de capacidade
    gondola: {
      type: Object,
      required: true
    }
  });
  
  const emit = defineEmits(['update:shelf', 'close']);
  
  // Quandidade local para o input
  const quantity = ref(props.shelf.quantity || 1);
  
  // Atualiza o valor local quando o shelf mudar
  watch(() => props.shelf.quantity, (newQuantity) => {
    quantity.value = newQuantity || 1;
  });
  
  // Calcula o número máximo de produtos que cabem na prateleira
  const maxProductsPerShelf = computed(() => {
    if (!props.shelf.product) return 0;
    
    // Cálculo baseado na largura da prateleira e do produto
    const shelfWidth = props.shelf.section.width;
    const productWidth = props.shelf.product.width;
    
    if (!productWidth || productWidth <= 0) return 1;
    
    // Considera um pequeno espaço entre os produtos (ex: 1cm)
    const spaceBetweenProducts = 1;
    const availableWidth = shelfWidth - spaceBetweenProducts;
    const maxProducts = Math.floor(availableWidth / (productWidth + spaceBetweenProducts));
    
    return Math.max(1, maxProducts);
  });
  
  // Função para atualizar a quantidade
  function updateQuantity(newQuantity: number) {
    const validatedQuantity = Math.min(
      Math.max(1, newQuantity), 
      maxProductsPerShelf.value
    );
    
    quantity.value = validatedQuantity;
    
    emit('update:shelf', {
      ...props.shelf,
      quantity: validatedQuantity
    });
  }
  
  // Validação ao mudar o input diretamente
  function validateAndUpdateQuantity() {
    let value = Number(quantity.value);
    
    // Garante que é um número válido
    if (isNaN(value) || value < 1) {
      value = 1;
    } else if (value > maxProductsPerShelf.value) {
      value = maxProductsPerShelf.value;
    }
    
    // Atualiza para o valor validado
    updateQuantity(value);
  }
  
  // Remove o produto da prateleira
  function removeProduct() {
    emit('update:shelf', {
      ...props.shelf,
      product: null,
      quantity: null
    });
  }
  </script>
  
  <style scoped>
  .product-settings-panel { 
    position: relative;
    z-index: 50;
  }
  </style>