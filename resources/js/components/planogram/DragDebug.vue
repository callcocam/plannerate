<template>
    <div class="drag-debug" v-if="isVisible">
        <div class="drag-debug-header">
            <h3>Diagnóstico de Arrasto</h3>
            <button @click="hide" class="close-btn">×</button>
        </div>
        <div class="drag-debug-content">
            <div class="debug-item">
                <span class="label">Arrastando:</span>
                <span class="value" :class="{ active: isDragging }">{{ isDragging ? 'Sim' : 'Não' }}</span>
            </div>
            <div class="debug-item">
                <span class="label">Prateleira ID:</span>
                <span class="value">{{ shelfId }}</span>
            </div>
            <div class="debug-item">
                <span class="label">Seção Original:</span>
                <span class="value">{{ originalSection }}</span>
            </div>
            <div class="debug-item">
                <span class="label">Seção Alvo:</span>
                <span class="value" :class="{ active: targetSection }">{{ targetSection || 'Nenhuma' }}</span>
            </div>
            <div class="debug-item">
                <span class="label">Posição Y:</span>
                <span class="value">{{ position }}px</span>
            </div>
            <div class="debug-item">
                <span class="label">Eventos Recentes:</span>
            </div>
            <div class="events-list">
                <div v-for="(event, index) in events" :key="index" class="event-item">
                    <span class="event-time">{{ event.time }}</span>
                    <span class="event-type">{{ event.type }}</span>
                    <span class="event-details">{{ event.details }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

const isVisible = ref(false);
const isDragging = ref(false);
const shelfId = ref('');
const originalSection = ref('');
const targetSection = ref('');
const position = ref(0);
const events = ref([]);

// Método para esconder o painel
const hide = () => {
    isVisible.value = false;
};

// Método para adicionar um evento ao log
const addEvent = (type, details = '') => {
    const now = new Date();
    const time = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}:${now.getSeconds().toString().padStart(2, '0')}.${now.getMilliseconds().toString().padStart(3, '0')}`;

    events.value.unshift({
        time,
        type,
        details,
    });

    // Manter apenas os 20 eventos mais recentes
    if (events.value.length > 20) {
        events.value.pop();
    }
};

// Adicionar ouvintes globais para capturar eventos
onMounted(() => {
    // Tecla D para mostrar/esconder
    window.addEventListener('keydown', (e) => {
        if (e.key === 'd' && e.ctrlKey) {
            isVisible.value = !isVisible.value;
            e.preventDefault();
        }
    });

    // Ouvir eventos personalizados
    window.addEventListener('shelf-drag-start', (e) => {
        isDragging.value = true;
        shelfId.value = e.detail.shelfId;
        originalSection.value = e.detail.sectionId;
        addEvent('Arrasto Iniciado', `Prateleira: ${e.detail.shelfId}`);
    });

    window.addEventListener('shelf-drag-move', (e) => {
        position.value = e.detail.position;
        if (e.detail.targetSection) {
            targetSection.value = e.detail.targetSection;
            addEvent('Sobre Seção', `Seção: ${e.detail.targetSection}`);
        }
    });

    window.addEventListener('shelf-drag-end', (e) => {
        isDragging.value = false;
        addEvent('Arrasto Finalizado', e.detail.transferred ? `Transferida para: ${e.detail.targetSection}` : 'Sem transferência');
        // Resetar após um curto atraso
        setTimeout(() => {
            targetSection.value = '';
        }, 1000);
    });
});

onUnmounted(() => {
    window.removeEventListener('keydown', null);
    window.removeEventListener('shelf-drag-start', null);
    window.removeEventListener('shelf-drag-move', null);
    window.removeEventListener('shelf-drag-end', null);
});
</script>

<style scoped>
.drag-debug {
    position: fixed;
    right: 20px;
    bottom: 20px;
    width: 350px;
    background-color: rgba(0, 0, 0, 0.85);
    color: white;
    border-radius: 8px;
    z-index: 9999;
    font-family: monospace;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.drag-debug-header {
    padding: 8px 12px;
    background-color: #3b82f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.drag-debug-header h3 {
    margin: 0;
    font-size: 14px;
}

.close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
    padding: 0 4px;
}

.drag-debug-content {
    padding: 12px;
}

.debug-item {
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.label {
    font-weight: bold;
    opacity: 0.8;
}

.value {
    background-color: rgba(255, 255, 255, 0.1);
    padding: 2px 6px;
    border-radius: 4px;
}

.value.active {
    background-color: #10b981;
    font-weight: bold;
}

.events-list {
    margin-top: 8px;
    background-color: rgba(0, 0, 0, 0.3);
    border-radius: 4px;
    padding: 4px;
    max-height: 200px;
    overflow-y: auto;
}

.event-item {
    padding: 4px;
    font-size: 12px;
    display: flex;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.event-time {
    color: #a3e635;
    margin-right: 8px;
    flex-shrink: 0;
}

.event-type {
    color: #38bdf8;
    margin-right: 8px;
    font-weight: bold;
    flex-shrink: 0;
}

.event-details {
    color: #fcd34d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
