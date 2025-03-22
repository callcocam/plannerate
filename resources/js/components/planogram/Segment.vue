<template>
    <div class="segment" >
        <LayerVue
            :key="segment.id"
            :shelf="shelf"
            :segment="segment"
            :layer="segment.layer"
            :scale-factor="scaleFactor"
            :gondola="gondola"
            @update:layer="updateLayer"
            @update:segment="updateSegment"
        />
    </div>
</template>
<script setup lang="ts"> 
import { Gondola, Layer, Segment, Shelf } from './planogram';
import LayerVue from './Layer.vue';

const props = defineProps<{ gondola: Gondola; shelf: Shelf; segment: Segment; scaleFactor: number }>();

const emit = defineEmits(['update:layer', 'update:segment']);
 

// Função para passar atualizações para o componente pai
const updateLayer = (layer: Layer) => {
    emit('update:layer', layer);
};

const updateSegment = (segment: Segment) => {
    emit('update:segment', segment);
};
</script>
