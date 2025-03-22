import { ref } from 'vue';

// Singleton instance for global state
const selectedLayerId = ref<string | null>(null);

export function useLayerSelection() {
  // Select a layer by ID
  function selectLayer(layerId: string) {
    selectedLayerId.value = layerId;
  }
  
  // Check if a layer is currently selected
  function isLayerSelected(layerId: string) {
    return selectedLayerId.value === layerId;
  }
  
  // Clear the current selection
  function clearSelection() {
    selectedLayerId.value = null;
  }
  
  return {
    selectLayer,
    isLayerSelected,
    clearSelection,
    selectedLayerId
  };
}