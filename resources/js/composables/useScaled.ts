export function useScaled(scaleFactor: number) {
    const widthScaled = (width: number) => Number(width) * Number(scaleFactor);
    const heightScaled = (height: number) => Number(height) * Number(scaleFactor);
    const holeSpacingScaled = (holeSpacing: number) => Number(holeSpacing) * Number(scaleFactor);
    const shelfHeightScaled = (shelfHeight: number) => Number(shelfHeight) * Number(scaleFactor);
    const thicknessScaled = (thickness: number) => Number(thickness) * Number(scaleFactor);
    const positionScaled = (position: number) => Number(position) * Number(scaleFactor);
    return {
        widthScaled,
        heightScaled,
        holeSpacingScaled,
        shelfHeightScaled,
        thicknessScaled,
        positionScaled,
    }
}