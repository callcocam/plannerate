// Composable para manipular e calcular propriedades relacionadas ao trilho da prateleira
import { computed } from 'vue';

// Interface para definição das propriedades do componente
interface ShelfTrackProps {
    width?: number;      // Largura do trilho em pixels
    height?: number;     // Altura total do trilho em pixels
    thickness?: number;  // Espessura do trilho em pixels
    shelfHeight?: number; // Altura dos furos para suporte
    holeSpacing?: number; // Espaçamento vertical entre os furos
    scaleFactor?: number; // Fator de escala para ajuste das dimensões
    baseHeightScaled?: number; // Altura base escalada
    position?: 'left' | 'right'; // Posição do trilho
}

// Função composable
export function useShelfTrack(props: ShelfTrackProps = {
    width: 30,
    height: 1200,
    thickness: 25,
    holeSpacing: 25,
    shelfHeight: 4,
    scaleFactor: 1,
    position: 'left',
    baseHeightScaled: 17
}) { 
    const widthScaled = (width: number) => Number(width) * Number(props.scaleFactor);
    const heightScaled = (height: number) => Number(height) * Number(props.scaleFactor);
    const holeSpacingScaled = (holeSpacing: number) => Number(holeSpacing) * Number(props.scaleFactor);
    const shelfHeightScaled = (shelfHeight: number) => Number(shelfHeight) * Number(props.scaleFactor);
    const thicknessScaled = (thickness: number) => Number(thickness) * Number(props.scaleFactor);
    // Constantes do componente
    const BASE_HOLE_WIDTH = 8;
    const HOLE_GAP_MULTIPLIER = 1;  


    // Dimensões escaladas do componente
    const scaledDimensions = computed(() => ({
        width: widthScaled(props.width ?? 30),
        scaleFactor: props.scaleFactor ?? 1,
        height: heightScaled(props.height ?? 1200),
        holeSpacing: holeSpacingScaled(props.holeSpacing ?? 4),
        holeWidth: BASE_HOLE_WIDTH,
        holeHeight: shelfHeightScaled(props.shelfHeight ?? 4),
        holeGap: HOLE_GAP_MULTIPLIER * (props.scaleFactor ?? 1),
        thickness: thicknessScaled(props.thickness ?? 25),
        shelfHeight: shelfHeightScaled(props.shelfHeight ?? 4),
        baseHeightScaled: props.baseHeightScaled ?? 17
    }));

    // Calcula o número total de furos baseado na altura utilizável
    const numberOfHoles = computed(() => { 
        const spacing = scaledDimensions.value.shelfHeight + scaledDimensions.value.holeSpacing;
        const usableHeight = scaledDimensions.value.height - scaledDimensions.value.baseHeightScaled - spacing ;
        console.log(usableHeight, 'usableHeight');
        return Math.floor(usableHeight / spacing);
    });

    // Gera array com as posições Y de cada par de furos
    const holes = computed(() => {
        const spacing = scaledDimensions.value.shelfHeight + scaledDimensions.value.holeSpacing;
        //Fazer sort dos furos descrescente
        const sortedHoles = Array.from({ length: numberOfHoles.value  }, (_, i) => ({
            y: i * spacing
        })).sort((a, b) => b.y - a.y); 
        return sortedHoles;
    });

    return {
        widthScaled,
        heightScaled,
        thicknessScaled,
        holeSpacingScaled,
        shelfHeightScaled,
        scaledDimensions,
        numberOfHoles,
        holes
    };
}
