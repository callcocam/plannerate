<template>
    <LayerContext :layer="layer" :gondola="gondola" :shelf="shelf" @delete="handleDelete">
        <div
            class="layer-container"
            :style="layerStyle"
            ref="layerRef"
            @click.stop="handleClick"
            draggable="true"
            @dragstart="handleDragStart"
            @dragend="handleDragEnd"
            :class="{ 'layer-dragging': isDragging, selected: isLayerSelected(layer.id) }"
            :data-layer-id="layer.id"
            :data-segment-id="segment.id"
        >
            <ProductView
                v-for="(_, index) in productItems"
                :key="`product-${index}`"
                :shelf="shelf"
                :layer="layer"
                :product="product"
                :scale-factor="scaleFactor"
                :style="getProductStyle(index)"
            />
            <!-- Alça de arrasto -->
            <div class="layer-drag-handle" title="Arraste para mover para outra prateleira">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M18 8L6 8"></path>
                    <path d="M18 16L6 16"></path>
                    <path d="M18 12L6 12"></path>
                </svg>
            </div>
        </div>
    </LayerContext>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import useLayerDrag from './../../composables/useLayerDrag';
import { useLayerSelection } from './../../composables/useLayerSelection';
import LayerContext from './context/LayerContext.vue';
import { Gondola, Layer, Segment, Shelf } from './planogram';
import ProductView from './ProductView.vue';

const props = defineProps<{
    gondola: Gondola;
    shelf: Shelf;
    segment: Segment;
    layer: Layer;
    scaleFactor: number;
}>();

const emit = defineEmits<{
    'update:shelf': [shelf: Shelf];
    'update:layer': [layer: Layer | Shelf];
    'update:segment': [segment: Segment | Shelf];
    'transfer-layer': [
        transferData: {
            layer: Layer;
            segment: Segment;
            fromShelfId: string;
            toShelfId: string;
        },
    ];
}>();

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

// Criar um wrapper do emit para compatibilidade de tipos
const emitWrapper = (event: string, ...args: any[]) => {
    if (event === 'update:shelf') emit('update:shelf', args[0]);
    else if (event === 'update:layer') emit('update:layer', args[0]);
    else if (event === 'update:segment') emit('update:segment', args[0]);
    else if (event === 'transfer-layer') emit('transfer-layer', args[0]);
};

// Usar o composable de drag and drop
const { isDragging, startDrag, cleanup } = useLayerDrag({
    layer: props.layer,
    segment: props.segment,
    shelf: props.shelf,
    emit: emitWrapper,
});

// Calculate styles for individual products when using justify
const getProductStyle = (index: number) => {
    if (!isJustified.value || layerQuantity.value <= 1) return {};

    return {
        display: 'flex',
        justifyContent: 'space-between',
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
        return total + segment.layer.product.width * segment.layer.quantity;
    }, 0);

    // Espaço livre na prateleira
    const freeSpace = totalShelfWidth - totalProductsWidth;

    return {
        totalWidth: totalShelfWidth,
        usedWidth: totalProductsWidth,
        freeSpace: freeSpace > 0 ? freeSpace : 0, // Garantimos que não seja negativo
        segmentCount: props.shelf.segments.length,
    };
});

// Define o modo de distribuição (pode ser obtido das configurações da prateleira ou definido por padrão)
const distributionMode = computed(() => {
    // Verifica se a prateleira tem configurações de distribuição
    const shelfSettings = props.shelf.settings
        ? typeof props.shelf.settings === 'string'
            ? JSON.parse(props.shelf.settings)
            : props.shelf.settings
        : {};

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
        display: 'flex' as const,
    };
});

// ==================
// State & Composables
// ==================
const { selectLayer: selectLayerFromComposable, isLayerSelected, clearSelection } = useLayerSelection();

// ==================
// Methods
// ==================
function handleClick() {
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

// Manipulador para iniciar o arrasto
const handleDragStart = (event: DragEvent) => {
    startDrag(event, layerRef.value);
};

// Manipulador para finalizar o arrasto
const handleDragEnd = () => {
    cleanup();
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
            .filter((s) => s.id !== segment.id)
            .reduce((total, s) => total + s.layer.product.width * s.layer.quantity, 0);

        // Calcular a largura atual deste segmento
        const currentSegmentWidth = product.width * currentQuantity;

        // Calcular largura disponível total (largura da seção - espessura da gôndola - largura dos outros produtos)
        const gondolaThickness = props.gondola.thickness;
        const availableWidth = props.shelf.section.width - 2 * gondolaThickness - existingWidth;

        // Verificar se há espaço suficiente
        const canFit = currentSegmentWidth + extraWidth <= availableWidth + currentSegmentWidth;

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
    cleanup(); // Limpar qualquer estado de arrasto pendente
});
</script>

<!-- Adicionar estilos para drag and drop -->
<style scoped>
.layer-container {
    position: absolute;
    display: flex;
    box-sizing: border-box;
    cursor: grab; /* Cursor indicando que é arrastável */
    transition:
        transform 0.15s ease,
        opacity 0.15s ease,
        box-shadow 0.15s ease;
    user-select: none; /* Evitar seleção de texto durante arrasto */
}

/* Estilo quando a layer está sendo arrastada */
.layer-container.layer-dragging {
    opacity: 0.6;
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    cursor: grabbing;
    z-index: 1000;
}

/* Estilo quando a layer está selecionada */
.layer-container.selected {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Alça de arrasto (drag handle) */
.layer-drag-handle {
    position: absolute;
    top: -18px;
    right: 5px;
    background-color: #4b5563;
    color: white;
    width: 16px;
    height: 16px;
    border-radius: 3px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: grab;
    opacity: 0;
    transition: opacity 0.2s ease;
    z-index: 10;
}

.layer-container:hover .layer-drag-handle {
    opacity: 0.8;
}

.layer-drag-handle:hover {
    opacity: 1;
    background-color: #3b82f6;
}

.layer-drag-handle:active {
    cursor: grabbing;
}

/* Estilo a ser adicionado globalmente ou em um componente pai */
:global(.layer-drop-target) {
    outline: 2px dashed #3b82f6 !important;
    background-color: rgba(59, 130, 246, 0.1) !important;
    position: relative;
}

:global(.shelf-container.layer-drop-target::before) {
    content: 'Soltar aqui';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: #3b82f6;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    pointer-events: none;
    z-index: 10;
}
</style>
