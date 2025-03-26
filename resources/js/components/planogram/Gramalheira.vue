<script setup lang="ts"> 
// @ts-ignore
import { cn } from '@/lib/utils';
import { type SeparatorProps } from 'radix-vue';
import { computed, ref, type HTMLAttributes } from 'vue';
import { Gondola } from './planogram';

const props = defineProps<SeparatorProps & {
    class?: HTMLAttributes['class'];
    gondola: Gondola & {
        height: number;
        base_height: number;
        shelf_height: number;
        hole_diameter: number;
        hole_spacing: number;
    };
    scaleFactor?: number;
}>();


const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;
    return delegated;
});

const effectiveScaleFactor = computed(() => {
    return props.scaleFactor !== undefined ? props.scaleFactor : props.gondola.scale_factor;
});

const gondolaContainerHeight = computed(() => {
    return props.gondola.height - props.gondola.base_height;
});
 
// Calcular as posições dos furos (mesmas posições usadas para as prateleiras)
const gramalheirHoles = computed(() => {
    if (!props.gondola || !props.gondola.height || !props.gondola.shelf_height) {
        return [] as number[];
    }

    const holes: number[] = [];
    const holeSpacing: any = props.gondola.shelf_height; // Usar o mesmo valor da altura da prateleira
    const totalHoles = Math.floor(gondolaContainerHeight.value / holeSpacing);

    for (let i = 0; i < totalHoles; i++) {
        const newHoleHeight = holeSpacing * i;
        // Verifica se o novo furo está dentro do limite da altura da gondola
        if (newHoleHeight > gondolaContainerHeight.value) {
            break;
        }
        holes.push(holeSpacing * i);
    }

    return holes;
});

// Estilo para cada furo
const gramalheirHoleStyle = (hole: number) => {
    const position = hole * effectiveScaleFactor.value; 
    return {
        width: `${props.gondola.hole_diameter * effectiveScaleFactor.value}px`,
        height: `${(props.gondola.shelf_height + props.gondola.hole_spacing) * effectiveScaleFactor.value}px`, // Altura reduzida para ficar mais estético
        position: 'absolute',
        top: `${position}px`,
        left: '50%',
        transform: 'translateX(-50%)',
        backgroundColor: 'white',
        borderColor: '#333',
        borderTopWidth: `${props.gondola.hole_spacing * effectiveScaleFactor.value}px`, // Largura da borda
        borderBottomWidth: `${props.gondola.hole_spacing * effectiveScaleFactor.value}px`, // Largura da borda
        borderLeftWidth: '2px',
        borderRightWidth: '2px',
        borderStyle: 'solid',
        zIndex: 10,
    };
}; 
</script>

<template>
    <div v-bind="delegatedProps" :class="cn('relative bg-gray-800 mx-auto', props.class)">
        <slot />
        <template v-for="(hole, index) in gramalheirHoles" :key="index">
            <!-- Furo centralizado -->
            <!-- @vue-ignore -->
            <div class="absolute z-30" :style="gramalheirHoleStyle(hole)">
            </div>
        </template>
    </div>
</template>