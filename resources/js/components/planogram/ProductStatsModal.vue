<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>Estatísticas do Produto</DialogTitle>
        <DialogDescription v-if="product">
          Informações detalhadas sobre {{ product.name }}
        </DialogDescription>
      </DialogHeader>
      
      <div class="space-y-4" v-if="product">
        <!-- Product basic info -->
        <div class="flex items-start space-x-4">
          <div class="flex-shrink-0 h-20 w-20 bg-gray-100 dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700 p-1 flex items-center justify-center">
            <img v-if="product.image_url" :src="product.image_url" 
                :alt="product.name" class="max-h-full max-w-full object-contain" />
            <Package v-else class="h-10 w-10 text-gray-400" />
          </div>
          
          <div>
            <h3 class="font-medium text-gray-900 dark:text-white">{{ product.name }}</h3>
            <p v-if="product.sku" class="text-sm text-gray-500">
              SKU: {{ product.sku }}
            </p>
          </div>
        </div>
        
        <!-- Product specifications -->
        <Separator />
        
        <div>
          <h4 class="text-sm font-medium mb-2">Especificações</h4>
          <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
            <div class="col-span-1">
              <dt class="text-gray-500">Dimensões</dt>
              <dd class="font-medium">{{ product.width }}×{{ product.height }}×{{ product.depth }} cm</dd>
            </div>
            <div class="col-span-1">
              <dt class="text-gray-500">Peso</dt>
              <dd class="font-medium">{{ product.weight || '---' }} kg</dd>
            </div>
            <div class="col-span-1">
              <dt class="text-gray-500">Estoque</dt>
              <dd class="font-medium">{{ product.stock_quantity || '0' }} unidades</dd>
            </div>
            <div class="col-span-1">
              <dt class="text-gray-500">Preço</dt>
              <dd class="font-medium">R$ {{ product.price ? parseFloat(product.price).toFixed(2) : '0.00' }}</dd>
            </div>
          </dl>
        </div>
        
        <!-- Product attributes -->
        <Separator />
        
        <div>
          <h4 class="text-sm font-medium mb-2">Atributos</h4>
          <div class="flex flex-wrap gap-2">
            <Badge v-if="product.hangable" variant="outline" class="bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800">Pendurável</Badge>
            <Badge v-if="product.stackable" variant="outline" class="bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800">Empilhável</Badge>
            <Badge v-if="product.flammable" variant="outline" class="bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-800">Inflamável</Badge>
            <Badge v-if="product.perishable" variant="outline" class="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800">Perecível</Badge>
            <Badge v-if="!product.hangable && !product.stackable && !product.flammable && !product.perishable" variant="outline" class="text-gray-500">Nenhum atributo especial</Badge>
          </div>
        </div>
        
        <!-- Future stats placeholder -->
        <Separator />
        
        <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-md">
          <h4 class="text-sm font-medium mb-2 flex items-center">
            <InfoIcon class="h-4 w-4 mr-2 text-gray-400" />
            Estatísticas Avançadas
          </h4>
          <p class="text-sm text-gray-500">
            Estatísticas detalhadas de vendas e desempenho estarão disponíveis em breve.
          </p>
        </div>
      </div>
      
      <DialogFooter>
        <Button variant="outline" @click="closeModal">Fechar</Button>
        <Button @click="selectProduct">Selecionar Produto</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, defineProps, defineEmits, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Package, InfoIcon } from 'lucide-vue-next';

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  product: {
    type: Object,
    default: null
  }
});

const emits = defineEmits(['update:open', 'select']);

// Local state
const isOpen = ref(props.open);

// Watch for external changes to open prop
watch(() => props.open, (newVal) => {
  isOpen.value = newVal;
});

// Watch for internal changes to isOpen
watch(isOpen, (newVal) => {
  emits('update:open', newVal);
});

// Close the modal
function closeModal() {
  isOpen.value = false;
}

// Select this product
function selectProduct() {
  if (props.product) {
    emits('select', props.product);
    closeModal();
  }
}
</script>
