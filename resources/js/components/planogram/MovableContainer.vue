<template>
    <div class="movable-container" ref="containerRef">
        <div class="movable-content" :style="contentStyle" :class="{ 'is-dragging': isDragging }">
            <!-- Alça de arrasto acoplada ao conteúdo -->
            <div
                title="Arraste para mover"
                class="drag-handle flex items-center justify-center bg-primary hover:bg-primary/90 text-primary-foreground shadow-md"
                @mousedown="startDrag"
                @touchstart="startDrag"
                :class="{ 'bg-primary/90': isDragging }"
            >
                <span class="sr-only">Mover Gôndola</span>
                <Move class="w-5 h-5" />
            </div>

            <!-- Slot para o conteúdo original (seu SectionList) -->
            <div class="content-slot ">
                <slot></slot>
            </div>

            <!-- Botão para centralizar também acoplado ao conteúdo -->
            <button 
                class="center-button bg-card hover:bg-accent text-muted-foreground hover:text-accent-foreground shadow-sm border border-border rounded-full flex items-center justify-center transition-all duration-200"
                @click="resetPosition" 
                title="Centralizar Gôndola"
            >
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
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <polyline points="9 21 3 21 3 15"></polyline>
                    <line x1="21" y1="3" x2="14" y2="10"></line>
                    <line x1="3" y1="21" x2="10" y2="14"></line>
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup>
import { Move } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    // ID opcional para salvar a posição
    storageId: {
        type: String,
        default: 'default-content',
    },
});

// Referências ao DOM e estado
const containerRef = ref(null);
const position = ref({ x: 0, y: 0 });
const isDragging = ref(false);

// Estado do arrasto
const dragState = ref({
    startX: 0,
    startY: 0,
    startPosX: 0,
    startPosY: 0,
    active: false,
});

// Estilo computado para o conteúdo
const contentStyle = computed(() => ({
    transform: `translate(${position.value.x}px, ${position.value.y}px)`,
}));

// Iniciar o arrasto
function startDrag(event) {
    // Prevenir comportamento padrão
    event.preventDefault();

    // Capturar posição inicial do mouse/toque
    const clientX = event.touches ? event.touches[0].clientX : event.clientX;
    const clientY = event.touches ? event.touches[0].clientY : event.clientY;

    // Configurar estado de arrasto
    dragState.value = {
        startX: clientX,
        startY: clientY,
        startPosX: position.value.x,
        startPosY: position.value.y,
        active: true,
    };

    isDragging.value = true;

    // Adicionar listeners de eventos
    if (event.touches) {
        document.addEventListener('touchmove', onDrag);
        document.addEventListener('touchend', stopDrag);
    } else {
        document.addEventListener('mousemove', onDrag);
        document.addEventListener('mouseup', stopDrag);
    }
}

// Processar o movimento durante o arrasto
function onDrag(event) {
    if (!dragState.value.active) return;

    event.preventDefault();

    const clientX = event.touches ? event.touches[0].clientX : event.clientX;
    const clientY = event.touches ? event.touches[0].clientY : event.clientY;

    // Calcular deslocamento
    const dx = clientX - dragState.value.startX;
    const dy = clientY - dragState.value.startY;

    // Atualizar posição
    position.value = {
        x: dragState.value.startPosX + dx,
        y: dragState.value.startPosY + dy,
    };
}

// Finalizar o arrasto
function stopDrag() {
    isDragging.value = false;
    dragState.value.active = false;

    // Remover listeners de eventos
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
    document.removeEventListener('touchmove', onDrag);
    document.removeEventListener('touchend', stopDrag);

    // Salvar posição
    savePosition();
}

// Centralizar o conteúdo
function resetPosition() {
    position.value = { x: 0, y: 0 };
    savePosition();
}

// Salvar posição no localStorage
function savePosition() {
    try {
        localStorage.setItem(`movable-content-${props.storageId}`, JSON.stringify(position.value));
    } catch (error) {
        console.error('Erro ao salvar posição:', error);
    }
}

// Carregar posição salva
function loadPosition() {
    try {
        const savedPos = localStorage.getItem(`movable-content-${props.storageId}`);
        if (savedPos) {
            position.value = JSON.parse(savedPos);
        }
    } catch (error) {
        console.error('Erro ao carregar posição:', error);
    }
}

// Verificar se o conteúdo está visível após redimensionamento
function checkVisibility() {
    if (!containerRef.value) return;

    const container = containerRef.value.getBoundingClientRect();
    const content = containerRef.value.querySelector('.movable-content')?.getBoundingClientRect();

    if (!content) return;

    // Se estiver totalmente fora da área visível, centralizar
    if (content.right < container.left || content.left > container.right || content.bottom < container.top || content.top > container.bottom) {
        resetPosition();
    }
}

// Ciclo de vida do componente
onMounted(() => {
    loadPosition();
    window.addEventListener('resize', checkVisibility);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
    document.removeEventListener('touchmove', onDrag);
    document.removeEventListener('touchend', stopDrag);
    window.removeEventListener('resize', checkVisibility);
});
</script>

<style scoped>
.movable-container {
    position: relative; 
    min-height: 300px;
    overflow: visible;
}

.movable-content {
    position: relative;
    transition: transform 0.1s ease;
    will-change: transform; 
}

.movable-content.is-dragging {
    transition: none;
}

.content-slot { 
    position: relative;
}

/* Estilo para a alça de arrasto */
.drag-handle {
    position: absolute;
    top: 50%;
    left: 0;
    transform: translateX(-100%);
    padding: 8px;
    border-radius: 9999px;
    cursor: move;
    z-index: 100;
    user-select: none;
    touch-action: none;
    transition: all 0.2s ease;
    width: 36px;
    height: 36px;
}

/* Estilo para o botão de centralizar */
.center-button {
    position: absolute;
    top: 15%;  
    right: -40px;
    width: 36px;
    height: 36px;
    z-index: 100;
}
 

/* Responsividade para telas menores */
@media (max-width: 640px) {
    .drag-handle {
        width: 32px;
        height: 32px;
        padding: 6px;
    }

    .center-button {
        width: 32px;
        height: 32px;
    }
}
</style>