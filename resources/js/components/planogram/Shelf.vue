<template>
    <!-- 
        ShelfContext: Componente de contexto que envolve a prateleira
        Recebe propriedades da prateleira e emite eventos para o componente pai
    -->
    <ShelfContext
        :shelf="shelf"
        :gondola="gondola"
        :shelfDirection="shelfDirection"
        @update:shelf="$emit('update:shelf', $event)"
        @delete="$emit('delete', $event)"
        @duplicate="$emit('duplicate', $event)"
        @move="$emit('move', $event)"
        @align="alignShelf"
        @edit-product="openProductSettings = true"
        @transfer="onTransferShelf"
    >
        <!-- 
            Container principal da prateleira
            Aplica classes condicionais baseadas no estado da prateleira
            Configura todos os manipuladores de eventos para interação de arrasto e toque
        -->
        <div
            class="shelf-container w-full bg-gray-700"
            :class="[
                { 'shelf-dragging': isDragging } /* Estilo quando está sendo arrastada */,
                { 'drop-target': isDropTarget } /* Estilo quando é alvo de um drop */,
                { 'has-product': !!shelf.segments.length } /* Estilo quando contém produtos */,
                { 'active-drop-zone': isDropTarget } /* Estilo adicional para zona de drop ativa */,
                { 'draggable-shelf': true },
                { 'shelf-selected': isSelected }, // Classe para indicar seleção
                { 'layer-drop-target': isDragOver } /* Estilo quando é alvo de drop de layer */,
            ]"
            :style="shelfStyle(shelf)"
            :data-shelf-id="shelf.id"
            @click.stop="onShelfClick"
            @mousedown="onMouseDown"
            @touchstart="onTouchStart"
            @dragenter.prevent="onDragOver"
            @dragover.prevent="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
            draggable="true"
        >
            <Move class="shelf-handle absolute -left-5 z-10 h-4 w-4 cursor-pointer text-white" />
            <!-- 
                Componente Segments: Renderiza os segmentos/produtos na prateleira
                Passa as props necessárias e configura handlers para eventos emitidos
            -->
            <Segments
                :shelf="shelf"
                :segments="shelf.segments"
                :gondola="gondola"
                :scale-factor="scaleFactor"
                @update:segment="updateSegment"
                @update:layer="updateLayer"
                @transfer-layer="handleTransferLayer"
            />

            <!-- Indicador de posição exibido durante o arrasto da prateleira -->
            <div v-if="isDragging" class="position-indicator">Base: {{ Math.round(getBasePosition()) }}cm</div>

            <!-- 
                Overlay para manipulação de drag & drop
                Cobre toda a prateleira e facilita a interação
                Só é visível durante o arrasto ou quando é um alvo de drop
            -->
            <div
                class="shelf-overlay absolute inset-0 cursor-move bg-gray-800 opacity-0 transition-colors hover:bg-gray-700"
                :class="{ 'opacity-50': isDropTarget || isDragOver }"
            >
                <!-- Indicador visual quando a prateleira é um alvo de soltar válido -->
                <div
                    v-if="isDropTarget || isDragOver"
                    class="bg-primary-500 pointer-events-none absolute inset-0 flex items-center justify-center bg-opacity-30"
                >
                    <div class="text-sm font-medium text-white">Soltar aqui</div>
                </div>
            </div>
        </div>
    </ShelfContext>
</template>

<script setup lang="ts">
/**
 * Componente Shelf
 * Representa uma prateleira individual dentro de uma gôndola
 * Gerencia posicionamento, arrastar/soltar e interação com produtos/segmentos
 */
import { Move } from 'lucide-vue-next';
import { computed, inject, onMounted, onUnmounted, ref, Ref, watch } from 'vue';
import useShelfDrag from './../../composables/useShelfDrag'; // Composable para lógica de drag & drop
import ShelfContext from './context/ShelfContext.vue';
import { Gondola, Layer, Segment, Shelf } from './planogram';
import Segments from './Segments.vue';

interface TransferLayerData {
    layer: Layer;
    segment: Segment;
    fromShelfId: string;
    toShelfId: string;
}

/**
 * Props do componente
 */
const props = defineProps<{
    gondola: Gondola;
    shelf: Shelf;
    shelfDirection: string;
    scaleFactor?: number;
    isFirst: boolean;
    isLast: boolean;
}>();

/**
 * Eventos emitidos pelo componente
 */
const emit = defineEmits<{
    'update:shelf': [shelf: Shelf];
    delete: [shelf: Shelf];
    duplicate: [shelf: Shelf];
    move: [shelf: Shelf];
    selectShelf: [shelf: Shelf];
    'update:layer': [layer: Layer];
    'update:segment': [segment: Segment | Shelf];
    'transfer-shelf': [
        transferData: {
            shelf: Shelf;
            fromSectionId: string;
            toSectionId: string;
            position?: number;
        },
    ];
    'transfer-layer': [transferData: TransferLayerData];
}>();

// Variáveis reativas para controle da posição da prateleira
const shelfPosition = ref<number>(props.shelf.position); // Posição atual
const minUpatePosition = ref<number>(props.shelf.position); // Posição mínima para atualização (evita atualizações desnecessárias)

// Variáveis para controle de drag & drop
const isDropTarget = ref<boolean>(false); // Indica se é alvo de um drop
const dragLeaveTimeout = ref<number | null>(null); // Timeout para controlar eventos de drag leave
const openProductSettings = ref<boolean>(false); // Controla abertura do painel de configurações do produto
const isDragOver = ref<boolean>(false); // Indica se uma layer está sendo arrastada sobre esta prateleira

/**
 * Observa mudanças na posição da prateleira nas props
 * Atualiza a posição local quando a posição nas props muda
 */
watch(
    () => props.shelf.position,
    (newPos) => {
        shelfPosition.value = newPos;
    },
);

/**
 * Inicializa a posição da prateleira quando o componente é montado
 */
onMounted(() => {
    shelfPosition.value = props.shelf.position;
});

/**
 * Calcula o fator de escala a ser utilizado
 * Usa o valor de props se disponível, caso contrário usa o da gôndola
 */
const scaleFactor = computed<number>(() => {
    return props.scaleFactor !== undefined ? props.scaleFactor : props.gondola.scale_factor;
});

// Referência local da direção das prateleiras
const shelfDirection = ref<string>(props.shelfDirection);

// Variáveis para controle do arraste
const isDragging = ref<boolean>(false); // Indica se está sendo arrastada
const startY = ref<number>(0); // Posição Y inicial do arraste
const originalPosition = ref<number>(0); // Posição original da prateleira antes do arraste
// Import activeShelf from provide/inject system
const activeShelf = inject<Ref<Shelf | null>>('activeShelf', ref(null));

/**
 * Obtém a posição da base da prateleira em centímetros
 * @returns {number} Posição em relação à base da gôndola
 */
const getBasePosition = (): number => {
    return shelfPosition.value;
};

/**
 * Calcula o estilo CSS para a prateleira
 * Ajusta posição, tamanho e z-index baseado nas propriedades e estado
 * @param {Shelf} shelf - Objeto da prateleira
 * @returns {Object} Objeto de estilo CSS
 */
const shelfStyle = (shelf: Shelf): Record<string, string> => {
    const { section } = shelf;
    let finalPosition;

    // Calcular dimensões com o fator de escala
    const width = section.width * scaleFactor.value;
    const height = props.gondola.shelf_height * scaleFactor.value;

    if (shelfDirection.value === 'top') {
        // Para direção top, usa a posição diretamente
        finalPosition = shelfPosition.value;

        // Aplicar o fator de escala à posição vertical
        const scaledPosition = finalPosition * scaleFactor.value;

        return {
            height: `${height}px`,
            width: `${width}px`,
            position: 'absolute',
            top: `${scaledPosition}px`,
            left: '0px',
            border: '1px solid #ccc',
            zIndex: '1000', // Aumenta z-index durante arrasto
        };
    } else {
        // Para direção bottom, calcula a posição a partir de baixo
        // Lógica especial para a primeira prateleira
        if (props.isFirst) {
            finalPosition = props.gondola.height - props.gondola.base_height - props.gondola.shelf_height;
        } else {
            finalPosition = props.gondola.height - shelfPosition.value - props.gondola.shelf_height;
        }

        // Aplicar o fator de escala à posição vertical
        const scaledPosition = finalPosition * scaleFactor.value;

        return {
            height: `${height}px`,
            width: `${width}px`,
            position: 'absolute',
            top: `${scaledPosition}px`,
            left: '0px',
            zIndex: '100', // Aumenta z-index durante arrasto
        };
    }
};

/**
 * Alinha a prateleira com o furo mais próximo na gramalheira
 * Calcula a posição ideal e emite evento para atualizar no componente pai
 */
const alignShelf = () => {
    const holeSpacing = props.gondola.hole_spacing;
    const baseHeight = props.gondola.base_height;

    const position = shelfPosition.value;
    const distanceFromBase = position - baseHeight;

    // Calcula o número de furos mais próximo
    const holes = Math.round(distanceFromBase / holeSpacing);
    // Calcula a posição alinhada ao furo
    const alignedPosition = baseHeight + holes * holeSpacing;

    shelfPosition.value = alignedPosition;

    // Evita atualizações desnecessárias se a mudança for menor que 1
    if (Math.abs(minUpatePosition.value - shelfPosition.value) < 1) return;

    minUpatePosition.value = shelfPosition.value;
    emit('update:shelf', { ...props.shelf, position: alignedPosition, preserveState: true } as Shelf);
};

/**
 * Repassa eventos de atualização de camada para o componente pai
 * @param {Layer | Shelf} layer - Camada ou prateleira atualizada
 */
const updateLayer = (layer: Layer | Shelf) => {
    emit('update:layer', layer as Layer);
};

/**
 * Repassa eventos de atualização de segmento para o componente pai
 * @param {Segment | Shelf} segment - Segmento ou prateleira atualizada
 */
const updateSegment = (segment: Segment | Shelf) => {
    emit('update:segment', segment);
};

/**
 * Usa o composable useShelfDrag para toda a lógica de arraste e soltar
 * Extrai métodos e variáveis do composable para uso no componente
 */
const {
    onDrop: onShelfDrop,
    onMouseDown, // Inicia arrasto com mouse
    onTouchStart, // Inicia arrasto com toque
    onDragOver: onShelfDragOver, // Manipula eventos durante arrasto sobre a prateleira
    onDragLeave: onShelfDragLeave, // Manipula saída do arrasto
} = useShelfDrag({
    props,
    shelfPosition,
    isDragging,
    startY,
    originalPosition,
    scaleFactor,
    emit,
    minUpatePosition,
    alignShelf,
    openProductSettings,
    isDropTarget,
    dragLeaveTimeout,
});

// Manipuladores para drag & drop com suporte a layers
const onDragOver = (event: DragEvent) => {
    event.preventDefault();
    onShelfDragOver(event); // Usar a implementação base para prateleiras

    // Verificar se o que está sendo arrastado é uma layer
    try {
        const dataType = event.dataTransfer?.types.find((type) => type === 'application/json');
        if (dataType) {
            const data = event.dataTransfer?.getData('application/json');
            if (data) {
                const draggedItem = JSON.parse(data);
                if (draggedItem.type === 'layer') {
                    isDragOver.value = true;
                }
            }
        }
    } catch (err) {
        // Se não conseguir ler os dados, apenas continue
    }

    // Limpar qualquer timeout existente
    if (dragLeaveTimeout.value) {
        window.clearTimeout(dragLeaveTimeout.value);
        dragLeaveTimeout.value = null;
    }
};

const onDragLeave = (event: DragEvent) => {
    onShelfDragLeave(event); // Usar a implementação base para prateleiras

    // Configurar um timeout para evitar que o evento dispare ao passar por elementos filhos
    dragLeaveTimeout.value = window.setTimeout(() => {
        isDragOver.value = false;
        isDropTarget.value = false;
    }, 50);
};

const onDrop = (event: DragEvent) => {
    event.preventDefault();
    isDropTarget.value = false;
    isDragOver.value = false;

    // Obter os dados do item arrastado
    const dataType = event.dataTransfer?.types.find((type) => type === 'application/json');
    if (!dataType) {
        onShelfDrop(event); // Usar a implementação base se não for um formato reconhecido
        return;
    }

    const data = event.dataTransfer?.getData('application/json');
    if (!data) {
        onShelfDrop(event); // Usar a implementação base se não tiver dados
        return;
    }

    try {
        // Processa os dados do item arrastado
        const draggedItem = JSON.parse(data);

        // Processa o drop conforme o tipo do item arrastado
        if (draggedItem.type === 'product') {
            // Implementação existente para produtos
            onShelfDrop(event);
        } else if (draggedItem.type === 'layer') {
            // Novo processamento para layers
            handleLayerDrop(draggedItem);
        } else {
            // Para outros tipos, usar a implementação base
            onShelfDrop(event);
        }
    } catch (err) {
        console.error('Error processing drop:', err);
        // Em caso de erro, usar a implementação base
        onShelfDrop(event);
    }
};

// Função para processar o drop de uma layer
const handleLayerDrop = (layerData: any) => {
    console.log('Layer drop recebido:', layerData);

    // Se a layer já está nesta prateleira, ignorar
    if (layerData.shelfId === props.shelf.id) return;

    // Emitir evento para transferir a layer para esta prateleira
    emit('transfer-layer', {
        fromShelfId: layerData.shelfId,
        toShelfId: props.shelf.id,
        layerId: layerData.layerId,
        segmentId: layerData.segmentId,
    } as any);
};

// Manipulador para o evento transfer-layer emitido pelos componentes filhos
const handleTransferLayer = (transferData: TransferLayerData) => {
    console.log('Propagando transferência de layer:', transferData);
    emit('transfer-layer', transferData);
};

// Modificar o evento de clique para emitir o evento selectShelf
const onShelfClick = (event: MouseEvent) => {
    // Previne a propagação para evitar outros handlers
    event.stopPropagation();

    // Se esta prateleira já está selecionada, deseleciona
    if (isSelected.value) {
        // Desseleciona a prateleira (como toggle)
        activeShelf.value = null;
    } else {
        // Seleciona esta prateleira e desseleciona qualquer outra
        activeShelf.value = props.shelf;

        // Emite o evento para o componente pai
        emit('selectShelf', props.shelf);
    }
};

// Manipulador para desselecionar quando clicar fora
onMounted(() => {
    // Adiciona event listener no documento para desselecionar quando clicar fora
    document.addEventListener('click', (e: MouseEvent) => {
        if (activeShelf.value && e.target instanceof Element && !e.target.closest('.shelf-container')) {
            activeShelf.value = null;
        }
    });
});

onUnmounted(() => {
    // Remove o event listener quando o componente é destruído
    document.removeEventListener('click', (e: MouseEvent) => {
        if (activeShelf.value && e.target instanceof Element && !e.target.closest('.shelf-container')) {
            activeShelf.value = null;
        }
    });
});

// Track if this shelf is currently selected
const isSelected = computed<boolean>(() => {
    return Boolean(activeShelf && activeShelf.value && activeShelf.value?.id === props.shelf.id);
});

/**
 * Transfere a prateleira para outra seção
 * @param {Object} transferData - Dados para transferência
 */
const onTransferShelf = (transferData: any) => {
    // Emite evento para o componente pai lidar com a transferência real
    emit('transfer-shelf', transferData);
};
</script>

<style scoped>
/* Estilo para indicar que o cursor pode arrastar verticalmente */
.cursor-move {
    cursor: ns-resize;
}

/* Estilo aplicado à prateleira durante arrasto */
.shelf-dragging {
    opacity: 0.8;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

/* Indicador de posição durante o arrasto */
.position-indicator {
    position: absolute;
    right: 60px;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    font-size: 12px;
    padding: 2px 4px;
    border-radius: 3px;
    pointer-events: none; /* Permite cliques através do indicador */
}

/* Estilo aplicado quando a prateleira é alvo de um drop */
.drop-target {
    box-shadow: inset 0 0 0 2px #3b82f6;
}

/* Estilo para zona de drop ativa */
.active-drop-zone {
    box-shadow: inset 0 0 0 2px #3b82f6;
    outline: 8px solid rgba(59, 130, 246, 0.2);
    outline-offset: 4px;
    z-index: 20 !important;
}

/* Estilo aplicado quando a prateleira tem produtos */
.has-product {
    background-color: #4b5563; /* bg-gray-600 para tema claro */
}

/* Garante que o escalamento seja aplicado corretamente */
.shelf-container * {
    box-sizing: border-box;
}

/* Estilo para a alça de arrasto */
.shelf-handle {
    opacity: 0;
    transition: opacity 0.2s ease;
    position: absolute;
    left: -18px;
    top: 50%;
    transform: translateY(-50%);
    background-color: #4b5563;
    padding: 4px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.shelf-container:hover .shelf-handle {
    opacity: 1;
}

/* Garantir estilo consistente no tema escuro */
:global(.dark) .shelf-container {
    background-color: #374151; /* bg-gray-700 para tema escuro */
}

:global(.dark) .has-product {
    background-color: #4b5563; /* bg-gray-600 para tema escuro */
}

:global(.dark) .position-indicator {
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

:global(.dark) .shelf-handle {
    background-color: #6b7280; /* bg-gray-500 para tema escuro */
}

/* Garante que o escalamento seja aplicado corretamente */
.shelf-container {
    box-sizing: border-box;
    position: relative;
    overflow: visible !important;
    background-color: #4b5563; /* Cor base para light mode */
}

/* Estilo para a prateleira selecionada */
.shelf-selected {
    opacity: 0.8;
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
    z-index: 5;
}

/* Estilo para quando uma layer está sendo arrastada sobre a prateleira */
.shelf-overlay {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.shelf-container.layer-drop-target {
    outline: 2px dashed #3b82f6;
    background-color: rgba(59, 130, 246, 0.1);
}
</style>
