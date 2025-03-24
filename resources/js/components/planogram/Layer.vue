<template>
    <LayerContext :layer="layer" :gondola="gondola" :shelf="shelf"  @delete="handleDelete">
        <div class="layer-container"   :style="layerStyle" ref="layerRef" @click.stop="handleClick">
            <ProductView
                v-for="(_, index) in productItems"
                :key="`product-${index}`"
                :shelf="shelf"
                :layer="layer"
                :product="product"
                :scale-factor="scaleFactor"
                :style="getProductStyle(index)"
            />
        </div>
    </LayerContext>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useLayerSelection } from './../../composables/useLayerSelection';
import LayerContext from './context/LayerContext.vue';
import { Gondola, Layer, Segment, Shelf } from './planogram';
import ProductView from './ProductView.vue';

const props = defineProps<{ gondola: Gondola; shelf: Shelf; segment: Segment; layer: Layer; scaleFactor: number }>();
const emit = defineEmits(['update:shelf', 'update:layer', 'update:segment']);

// ==================
// Computed Properties
// ==================
const layerQuantity = computed(() => props.segment.layer.quantity);
const layerHeight = computed(() => {
    const { layer, shelf, scaleFactor, segment } = props;
    const height = layer.product.height * segment.quantity || 1;
    return height * scaleFactor;
});
const selectedLayer = ref<Layer | null>(null);
const layerRef = ref<HTMLElement | null>(null);
const product = computed(() => {
    return props.layer.product;
}); 
const layerSettings = ref(props.layer?.settings ? JSON.parse(props.layer.settings) : {});

// Track alignment settings
const horizontalAlignment = ref(layerSettings.value?.horizontal_alignment || 'left');
const verticalAlignment = ref(layerSettings.value?.vertical_alignment || 'bottom');
const isJustified = ref(layerSettings.value?.justify || false);

// Generate correct number of product items based on quantity
const productItems = computed(() => {
    return Array.from({ length: layerQuantity.value }, (_, i) => i);
});
 

// Calculate styles for individual products when using justify
const getProductStyle = (index: number) => {
    if (!isJustified.value || layerQuantity.value <= 1) return {};

    // For justified layout, we calculate width to ensure products are evenly spaced
    // const productWidth = props.layer.product.width * props.scaleFactor;
    // const totalWidth = (props.segment.width - props.gondola.thickness * 2) * props.scaleFactor;
    // When justified, each product gets equal width of the container
    return {
        // width: `${totalWidth / layerQuantity.value}px`,
        display: 'flex',
        justifyContent: 'center',
    };
};

/**
 * Calcula o espaço total disponível e o espaço livre na prateleira
 */
const availableSpace = computed(() => {
    // Largura total da prateleira disponível para produtos (menos a espessura das bordas)
    const totalShelfWidth = props.shelf.section.width - props.gondola.thickness * 2;
    
    // Largura total usada por todos os produtos em todos os segmentos
    const totalProductsWidth = props.shelf.segments.reduce((total, segment) => {
        return total + (segment.layer.product.width * segment.layer.quantity);
    }, 0);
    
    // Espaço livre na prateleira
    const freeSpace = totalShelfWidth - totalProductsWidth;
    
    return {
        totalWidth: totalShelfWidth,
        usedWidth: totalProductsWidth,
        freeSpace: freeSpace > 0 ? freeSpace : 0, // Garantimos que não seja negativo
        segmentCount: props.shelf.segments.length
    };
});

// Define o modo de distribuição (pode ser obtido das configurações da prateleira ou definido por padrão)
const distributionMode = computed(() => {
    // Verifica se a prateleira tem configurações de distribuição
    const shelfSettings = props.shelf.settings ? 
        (typeof props.shelf.settings === 'string' ? JSON.parse(props.shelf.settings) : props.shelf.settings) : 
        {};
    
    // Obtém o modo de distribuição das configurações ou usa 'equal' como padrão
    return shelfSettings.distributionMode || 'proportional';
});

const layerStyle = computed(() => {
    const { gondola, layer, shelf, scaleFactor, segment } = props;
    
    // Largura base do segmento (produto * quantidade)
    let width = layer.product.width * layer.quantity;
    
    // Posição base (começa após a espessura da gôndola)
    let position = gondola.thickness;
    
    // Calcular distribuição de espaço mesmo quando há um único segmento
    // A condição permite distribuir espaço tanto para múltiplos segmentos quanto para um único segmento
    if (props.shelf.segments.length >= 1) {
        // Obter dados sobre espaço disponível
        const space = availableSpace.value;
        
        // Escolhe o modo de distribuição
        const mode = distributionMode.value; 
        // Calcular espaço extra para este segmento baseado no modo de distribuição
        if (mode === 'equal' || mode === 'proportional') {
            let extraSpace = 0;
            
            if (mode === 'equal') {
                // Distribuição igual entre todos os segmentos
                extraSpace = space.freeSpace / space.segmentCount;
            } else {
                // Distribuição proporcional ao tamanho do segmento
                const segmentRatio = (layer.product.width * layer.quantity) / space.usedWidth;
                extraSpace = space.freeSpace * segmentRatio;
            }
            
            // Adiciona o espaço extra à largura do segmento
            width += extraSpace;
        }
        
        // Calcula a posição com base em segmentos anteriores
        const previousSegments = props.shelf.segments.filter((s) => s.ordering < segment.ordering);
        
        if (previousSegments.length > 0) {
            if (mode === 'justify') {
                // Para o modo justificar, adiciona espaço igual entre segmentos
                const gapSize = space.segmentCount > 1 ? space.freeSpace / (space.segmentCount - 1) : 0;
                
                let additionalPosition = 0;
                for (let i = 0; i < previousSegments.length; i++) {
                    const seg = previousSegments[i];
                    // Adiciona a largura do segmento
                    additionalPosition += seg.layer.product.width * seg.layer.quantity;
                    // Adiciona o espaço do gap após este segmento
                    if (i < previousSegments.length) {
                        additionalPosition += gapSize;
                    }
                }
                
                position += additionalPosition;
            } else {
                // Para modos 'equal' e 'proportional'
                position += previousSegments.reduce((acc, seg) => {
                    let segWidth = seg.layer.product.width * seg.layer.quantity;
                    
                    if (mode === 'equal') {
                        segWidth += space.freeSpace / space.segmentCount;
                    } else if (mode === 'proportional') {
                        const ratio = (seg.layer.product.width * seg.layer.quantity) / space.usedWidth;
                        segWidth += space.freeSpace * ratio;
                    }
                    
                    return acc + segWidth;
                }, 0);
            }
        }
    }

    return {
        position: 'absolute' as const,
        bottom: `${gondola.shelf_height * scaleFactor}px`,
        left: `${position * scaleFactor}px`,
        height: `${layerHeight.value}px`,
        width: `${width * scaleFactor}px`,
        display: 'flex',
    };
});

// ==================
// State & Composables
// ==================
const { selectLayer: selectLayerFromComposable, isLayerSelected, clearSelection } = useLayerSelection();

// ==================
// Methods
// ==================
async function handleClick() {
    selectLayer();
}

const selectLayer = () => {
    selectLayerFromComposable(props.layer.id);
};
 
// Handle layer deletion
const handleDelete = (layer: Layer) => {
    // Create a copy of the shelf without the deleted layer's segment
    const updatedSegments = props.shelf.segments.filter((s) => !(s.id === props.segment.id && s.layer.id === layer.id));

    const updatedShelf = {
        ...props.shelf,
        segments: updatedSegments,
    };

    emit('update:layer', updatedShelf);
};
 

const handleKeyDown = (event: KeyboardEvent) => {
    if (!isLayerSelected(props.segment.layer.id)) return;

    const action = {
        Delete: async () => {
            console.log('deleteLayer');
            // emit('delete', selectedLayer.value);
        },
        ArrowRight: () => updateLayerQuantity(horizontalAlignment.value === 'right' ? layerQuantity.value - 1 : layerQuantity.value + 1),
        ArrowLeft: () => updateLayerQuantity(horizontalAlignment.value === 'right' ? layerQuantity.value + 1 : layerQuantity.value - 1),
        ArrowUp: () => updateSegmentQuantity(props.segment.quantity + 1),
        ArrowDown: () => updateSegmentQuantity(props.segment.quantity - 1),
        Escape: async () => clearSelection(),
    }[event.key];
    if (action) {
        action();
    } else if (/^[0-9]$/.test(event.key)) {
        updateLayerQuantity(parseInt(event.key));
    }
};

// ==================
// API Functions
// ==================
/**
 * Verifica se é possível aumentar a quantidade do produto na camada
 * @param {number} newQuantity - Nova quantidade desejada
 * @returns {boolean} Verdadeiro se a alteração é possível
 */
function validateQuantityChange(newQuantity: number): boolean {
    try {
        const currentQuantity = layerQuantity.value;
        
        // Se estiver diminuindo, sempre é válido
        if (newQuantity <= currentQuantity) return true;
        
        const { layer, shelf, segment } = props;
        const product = layer.product;
        
        // Calcular a largura extra necessária
        const extraWidth = product.width * (newQuantity - currentQuantity);
        
        // Calcular a largura total dos produtos existentes (excluindo este segmento)
        const existingWidth = shelf.segments
            .filter(s => s.id !== segment.id)
            .reduce((total, s) => total + (s.layer.product.width * s.layer.quantity), 0);
        
        // Calcular a largura atual deste segmento
        const currentSegmentWidth = product.width * currentQuantity;
        
        // Calcular largura disponível total (largura da seção - espessura da gôndola - largura dos outros produtos)
        const gondolaThickness = props.gondola.thickness;
        const availableWidth = props.shelf.section.width - (2 * gondolaThickness) - existingWidth;
        
        // Verificar se há espaço suficiente
        const canFit = (currentSegmentWidth + extraWidth) <= (availableWidth + currentSegmentWidth);
        
        if (!canFit) {
            alert(`Não há espaço suficiente na prateleira para aumentar para ${newQuantity} itens.
                  Espaço disponível: ${availableWidth + currentSegmentWidth}cm,
                  Espaço necessário: ${currentSegmentWidth + extraWidth}cm.`);
        }
        
        return canFit;
    } catch (error) {
        console.error('Erro ao validar alteração de quantidade:', error);
        return false;
    }
}

// Modificar a função updateLayerQuantity para incluir validação
function updateLayerQuantity(newQuantity: number) {
    try {
        // Não permitir quantidades menores que 1
        if (newQuantity < 1) return;
        
        // Validar se a nova quantidade cabe na prateleira
        if (!validateQuantityChange(newQuantity)) return;

        const { layer } = props.segment;
        if (layer.spacing > 0) {
            layer.spacing = 0;
        }

        delete layer.settings;
        // Atualizar localmente para feedback imediato
        const updatedLayer = {
            ...layer,
            quantity: newQuantity,
        };

        // Criar uma cópia atualizada do segmento
        const updatedSegment = {
            ...props.segment,
            layer: updatedLayer,
        };

        // Criar uma cópia atualizada da prateleira com o segmento atualizado
        const updatedSegments = [...props.shelf.segments];
        const segmentIndex = updatedSegments.findIndex((s) => s.id === props.segment.id);
        if (segmentIndex !== -1) {
            updatedSegments[segmentIndex] = updatedSegment;
        }

        const updatedShelf = {
            ...props.shelf,
            segments: updatedSegments,
        };

        // Enviar para o backend
        const data: any = {
            ...layer,
            quantity: newQuantity,
        };

        delete data.settings;
        // @ts-ignore
        router.put(route('layers.update', { layer: layer.id }), data, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                // Emitir o evento imediatamente para atualização visual instantânea
                emit('update:layer', updatedShelf);
            },
            onError: (errors) => {
                console.error('Erro ao atualizar quantidade de layer:', errors);
                // Em caso de erro, poderia emitir outro evento para reverter a UI
            },
        });
    } catch (error: any) {
        console.error('Erro ao atualizar quantidade de layer:', error);
    }
}

/**
 * Atualiza a quantidade de produtos no segmento via API
 */
function updateSegmentQuantity(newQuantity: number) {
    try {
        // Não permitir quantidades menores que 1
        if (newQuantity < 1) return;

        const { segment } = props;

        // Atualizar localmente para feedback imediato
        const updatedSegment = {
            ...segment,
            quantity: newQuantity,
        };

        // Criar uma cópia atualizada da prateleira com o segmento atualizado
        const updatedSegments = [...props.shelf.segments];
        const segmentIndex = updatedSegments.findIndex((s) => s.id === segment.id);
        if (segmentIndex !== -1) {
            updatedSegments[segmentIndex] = updatedSegment;
        }

        const updatedShelf = {
            ...props.shelf,
            segments: updatedSegments,
        };

        // Enviar para o backend
        const data: any = {
            ...segment,
            quantity: newQuantity,
        };

        // Remover propriedades desnecessárias, que podem causar erro na API
        delete data.settings;
        delete data.layer.settings;
        // @ts-ignore
        router.put(route('segments.update', { segment: segment.id }), data, {
            preserveScroll: true,
            preserveState: true, // Manter o estado atual durante a requisição
            onSuccess: () => {
                // Emitir evento para atualizar a prateleira no componente pai
                emit('update:segment', updatedShelf);
            },
            onError: (errors) => {
                console.error('Erro ao atualizar quantidade de segmento:', errors);
            },
        });
    } catch (error: any) {
        console.error('Erro ao atualizar quantidade de segmento:', error);
    }
}

const handleOutsideClick = (event: MouseEvent) => {
    const isDrawerClick = (event.target as HTMLElement)?.closest('.shel-edit-drawer');
    const isLayerClick = layerRef.value?.contains(event.target as Node);

    if (!isLayerClick && !isDrawerClick) {
        clearSelection();
    }
};

// ==================
// Lifecycle Hooks
// ==================
onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
    document.addEventListener('click', handleOutsideClick);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('click', handleOutsideClick);
});
</script>

<style scoped>
.layer-container {
    position: absolute;
    display: flex;
    box-sizing: border-box;
}

/* Additional styling for when the layer is selected */
.layer-container.selected {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}
</style>