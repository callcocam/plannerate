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
    > 
        <!-- 
            Container principal da prateleira
            Aplica classes condicionais baseadas no estado da prateleira
            Configura todos os manipuladores de eventos para interação de arrasto e toque
        -->
        <div
            class="shelf-container w-full bg-gray-700"
            :class="[
                { 'shelf-dragging': isDragging },        /* Estilo quando está sendo arrastada */
                { 'drop-target': isDropTarget },         /* Estilo quando é alvo de um drop */
                { 'has-product': !!shelf.segments.length }, /* Estilo quando contém produtos */
                { 'active-drop-zone': isDropTarget },    /* Estilo adicional para zona de drop ativa */
            ]"
            :style="shelfStyle(shelf)"                   
            @mousedown="onMouseDown"                      
            @touchstart="onTouchStart"                   
            @dragenter.prevent="onDragOver"               
            @dragover.prevent="onDragOver"               
            @dragleave="onDragLeave"                    
            @drop="onDrop"                    
        >
        <Move class="absolute -left-5 text-white cursor-pointer w-4 h-4 z-10 shelf-handle"/>
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
            />
            
            <!-- Indicador de posição exibido durante o arrasto da prateleira -->
            <div v-if="isDragging" class="position-indicator">Base: {{ Math.round(getBasePosition()) }}cm</div>
            
            <!-- 
                Overlay para manipulação de drag & drop
                Cobre toda a prateleira e facilita a interação
            -->
            <div class="absolute inset-0 cursor-move bg-gray-800 opacity-50 transition-colors hover:bg-gray-700" >
                <!-- Indicador visual quando a prateleira é um alvo de soltar válido -->
                <div v-if="isDropTarget" class="bg-primary-500 pointer-events-none absolute inset-0 flex items-center justify-center bg-opacity-30">
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
import { computed, onMounted, ref, watch } from 'vue';
import useShelfDrag from './../../composables/useShelfDrag'; // Composable para lógica de drag & drop
import ShelfContext from './context/ShelfContext.vue';
import { Gondola, Layer, Product, Segment, Shelf } from './planogram';
import Segments from './Segments.vue';
import { Move } from 'lucide-vue-next';

/**
 * Props do componente
 * @property {Gondola} gondola - Objeto com dados da gôndola
 * @property {Shelf} shelf - Objeto com dados da prateleira
 * @property {string} shelfDirection - Direção das prateleiras ('top' ou 'bottom')
 * @property {number} scaleFactor - Fator de escala para dimensionamento visual
 * @property {boolean} isFirst - Indica se é a primeira prateleira da seção
 * @property {boolean} isLast - Indica se é a última prateleira da seção
 */
const props = defineProps<{ 
    gondola: Gondola; 
    shelf: Shelf; 
    shelfDirection: string; 
    scaleFactor?: number; 
    isFirst: boolean; 
    isLast: boolean 
}>();

/**
 * Eventos emitidos pelo componente
 * Permite comunicação com o componente pai
 */
const emit = defineEmits([
    'update:shelf',   // Atualização da prateleira
    'delete',         // Exclusão da prateleira
    'duplicate',      // Duplicação da prateleira
    'move',           // Movimentação da prateleira
    'selectShelf',    // Seleção da prateleira
    'update:layer',   // Atualização de uma camada
    'update:segment'  // Atualização de um segmento
]);

// Variáveis reativas para controle da posição da prateleira
const shelfPosition = ref(props.shelf.position);  // Posição atual
const minUpatePosition = ref(props.shelf.position); // Posição mínima para atualização (evita atualizações desnecessárias)

// Variáveis para controle de drag & drop
const isDropTarget = ref(false);  // Indica se é alvo de um drop
const dragLeaveTimeout = ref(null); // Timeout para controlar eventos de drag leave
const openProductSettings = ref(false); // Controla abertura do painel de configurações do produto

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
const scaleFactor = computed(() => {
    return props.scaleFactor !== undefined ? props.scaleFactor : props.gondola.scale_factor;
});

// Referência local da direção das prateleiras
const shelfDirection = ref(props.shelfDirection);

// Variáveis para controle do arraste
const isDragging = ref(false);     // Indica se está sendo arrastada
const startY = ref(0);             // Posição Y inicial do arraste
const originalPosition = ref(0);   // Posição original da prateleira antes do arraste

/**
 * Obtém a posição da base da prateleira em centímetros
 * @returns {number} Posição em relação à base da gôndola
 */
const getBasePosition = () => {
    return shelfPosition.value;
};

/**
 * Calcula o estilo CSS para a prateleira
 * Ajusta posição, tamanho e z-index baseado nas propriedades e estado
 * @param {Shelf} shelf - Objeto da prateleira
 * @returns {Object} Objeto de estilo CSS
 */
const shelfStyle = (shelf: any) => {
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
            position: 'absolute' as const, // Type assertion para o tipo literal 'absolute'
            top: `${scaledPosition}px`,
            left: '0px',
            zIndex: isDragging.value ? 10 : 1, // Aumenta z-index durante arrasto
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
            position: 'absolute' as const, // Type assertion para o tipo literal 'absolute'
            top: `${scaledPosition}px`,
            left: '0px',
            zIndex: isDragging.value ? 10 : 1, // Aumenta z-index durante arrasto
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
    emit('update:shelf', { ...props.shelf, position: alignedPosition });
};

/**
 * Repassa eventos de atualização de camada para o componente pai
 * @param {Layer} layer - Camada atualizada
 */
const updateLayer = (layer: Layer) => {
    emit('update:layer', layer);
};

/**
 * Repassa eventos de atualização de segmento para o componente pai
 * @param {Segment} segment - Segmento atualizado
 */
const updateSegment = (segment: Segment) => {
    emit('update:segment', segment);
};

/**
 * Usa o composable useShelfDrag para toda a lógica de arraste e soltar
 * Extrai métodos e variáveis do composable para uso no componente
 */
const { 
    onMouseDown,      // Inicia arrasto com mouse
    onTouchStart,     // Inicia arrasto com toque
    onDragOver,       // Manipula eventos durante arrasto sobre a prateleira
    onDragLeave,      // Manipula saída do arrasto 
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

/**
 * Verifica se um produto cabe na prateleira atual
 * @param {Product} product - O produto a verificar
 * @param {number} quantity - Quantidade do produto
 * @returns {boolean} Verdadeiro se o produto cabe na prateleira
 */
const validateProductFit = (product: Product, quantity = 1) => {
    // Verificar altura
    // if (product.height > props.shelf.height) {
    //     alert(`O produto é muito alto para esta prateleira.
    //           Altura do produto: ${product.height}cm,
    //           altura da prateleira: ${props.shelf.height}cm.`);
    //     return false;
    // }
    
    // Calcular largura total dos produtos existentes
    const existingWidth = props.shelf.segments.reduce(
        (carry, segment) => {
            return carry + (segment.layer.product.width * segment.layer.quantity);
        }, 0);
    
    // Calcular largura necessária para o novo produto
    const requiredWidth = product.width * quantity;
    
    // Calcular largura disponível (largura da seção - espessura da gôndola - largura dos produtos existentes)
    const gondolaThickness = props.gondola.thickness;
    const availableWidth = props.shelf.section.width - (2 * gondolaThickness) - existingWidth;
    
    if (requiredWidth > availableWidth) {
        alert(`Não há espaço suficiente na prateleira.
              Largura necessária: ${requiredWidth}cm,
              espaço disponível: ${availableWidth}cm.`);
        return false;
    }
    
    return true;
};

// Modificar a função onDrop para incluir a validação
const { 
    onDrop: originalOnDrop,
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

// Sobrescrevendo onDrop para incluir validação
const onDrop = (event: DragEvent) => {
    event.preventDefault();
    isDropTarget.value = false;

    // Limpa o timeout se existir
    if (dragLeaveTimeout.value) {
        clearTimeout(dragLeaveTimeout.value);
        dragLeaveTimeout.value = null;
    }

    try {
        const jsonData = event.dataTransfer?.getData('application/json');
        if (jsonData) {
            const product = JSON.parse(jsonData);
            
            // Validar se o produto cabe antes de adicionar
            if (!validateProductFit(product)) {
                return; // Não adiciona se não couber
            }

            // O resto do código continua como antes
            // ...existing code from originalOnDrop...
            
            // Create a new segment with all necessary properties
            const newSegment = {
                id: `segment-${Date.now()}`,
                width: props.shelf.section?.width || 130,
                ordering: (props.shelf.segments?.length || 0) + 1,
                quantity: 1,
                spacing: 0,
                position: 0,
                status: 'published',
                // Create layer with product information
                layer: {
                    id: `layer-${Date.now()}`,
                    product_id: product.id,
                    product_name: product.name,
                    product_image: product.image,
                    height: product.height,
                    spacing: 0,
                    quantity: 1,
                    status: 'published',
                }
            };

            // Update the shelf with the new segment
            const updatedShelf = {
                ...props.shelf,
                segment: newSegment,
            };

            // Emite o evento para atualizar a prateleira
            emit('update:shelf', updatedShelf);

            // Emite o evento selectShelf quando um produto é colocado
            emit('selectShelf', props.shelf);
        }
    } catch (error) {
        console.error('Erro ao processar o produto arrastado:', error);
    }
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
    white-space: nowrap;
    z-index: 20;
}

/* Estilo global para o corpo durante arrasto de prateleira */
:global(body.shelf-dragging) {
    cursor: ns-resize !important;
    user-select: none; /* Impede seleção de texto durante arrasto */
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

.shelf-container * {
    box-sizing: border-box;
}
</style>