// useLayerDrag.ts
import { ref, Ref } from 'vue'; 
import { Layer, Segment, Shelf } from '../components/planogram/planogram';

interface TransferData {
  layer: Layer;
  segment: Segment;
  fromShelfId: string;
  toShelfId: string;
}

interface UseLayerDragOptions {
  layer: Layer;
  segment: Segment;
  shelf: Shelf;
  emit: (event: string, ...args: any[]) => void;
}

interface UseLayerDragReturn {
  isDragging: Ref<boolean>;
  startDrag: (event: DragEvent, element: HTMLElement | null) => void;
  cleanup: () => void;
}

/**
 * Composable para gerenciar o arrasto e soltura de layers entre prateleiras
 */
export default function useLayerDrag({ layer, segment, shelf, emit }: UseLayerDragOptions): UseLayerDragReturn {
  // Estado para rastrear a operação de arrasto
  const isDragging = ref<boolean>(false);
  const dragImage = ref<HTMLElement | null>(null);
  const originalShelf = ref<Shelf | null>(null);
  const targetShelf = ref<string | null>(null);
  const layerElement = ref<HTMLElement | null>(null);

  /**
   * Inicializa o arrasto da layer
   */
  const startDrag = (event: DragEvent, element: HTMLElement | null) => {
    if (!event.dataTransfer) {
      console.error('DataTransfer não está disponível.');
      return;
    }

    // Salvar referência ao elemento e prateleira original
    layerElement.value = element;
    originalShelf.value = shelf;
    
    // Definir os dados a serem transferidos
    const transferData = {
      type: 'layer',
      layerId: layer.id,
      segmentId: segment.id,
      shelfId: shelf.id,
      product: layer.product,
      quantity: layer.quantity
    };
    
    // Configurar o dataTransfer para o drag and drop nativo
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('application/json', JSON.stringify(transferData));
    
    // Adicionar classe de estilo para feedback visual
    if (layerElement.value) {
      layerElement.value.classList.add('layer-dragging');
    }
    
    // Criar imagem personalizada para o arrasto (opcional)
    createDragImage(event);
    
    // Definir estado de arrastrando
    isDragging.value = true;
    
    // Disparar evento personalizado para debugging
    window.dispatchEvent(new CustomEvent('layer-drag-start', { 
      detail: { 
        layerId: layer.id, 
        shelfId: shelf.id 
      } 
    }));
    
    // Adicionar ouvintes globais para rastrear o arrasto
    document.addEventListener('dragover', handleDragOver);
    document.addEventListener('drop', handleDrop);
    document.addEventListener('dragend', handleDragEnd);
  };

  /**
   * Cria uma imagem personalizada para o arrastar
   */
  const createDragImage = (event: DragEvent) => {
    if (!event.dataTransfer) return;

    const productName = layer.product?.name || 'Produto';
    
    // Criar elemento para representar visualmente o que está sendo arrastado
    const ghostElement = document.createElement('div');
    ghostElement.className = 'layer-drag-ghost';
    ghostElement.innerHTML = `
      <div class="product-preview">
        <div class="product-image">
          ${layer.product?.image_url ? `<img src="${layer.product.image_url}" alt="${productName}">` : ''}
        </div>
        <div class="product-info">
          <div class="product-name">${productName}</div>
          <div class="product-quantity">Qtd: ${layer.quantity}</div>
        </div>
      </div>
    `;
    
    // Estilizar o elemento fantasma
    Object.assign(ghostElement.style, {
      position: 'absolute',
      top: '0',
      left: '0',
      width: '200px',
      padding: '8px',
      backgroundColor: 'rgba(59, 130, 246, 0.9)',
      color: 'white',
      borderRadius: '4px',
      boxShadow: '0 2px 10px rgba(0,0,0,0.2)',
      zIndex: '9999',
      pointerEvents: 'none',
      transform: 'translate(-50%, -50%)',
      fontSize: '12px'
    });
    
    // Estilizar a imagem e info do produto
    const productPreview = ghostElement.querySelector('.product-preview');
    if (productPreview) {
      Object.assign((productPreview as HTMLElement).style, {
        display: 'flex',
        alignItems: 'center'
      });
    }
    
    const productImage = ghostElement.querySelector('.product-image');
    if (productImage) {
      Object.assign((productImage as HTMLElement).style, {
        width: '40px',
        height: '40px',
        marginRight: '8px',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: 'white',
        borderRadius: '4px',
        overflow: 'hidden'
      });
    }
    
    const img = ghostElement.querySelector('img');
    if (img) {
      Object.assign((img as HTMLElement).style, {
        maxWidth: '100%',
        maxHeight: '100%',
        objectFit: 'contain'
      });
    }
    
    // Adicionar ao DOM
    document.body.appendChild(ghostElement);
    dragImage.value = ghostElement;
    
    // Definir como imagem de arrasto
    if (event.dataTransfer.setDragImage) {
      event.dataTransfer.setDragImage(ghostElement, 100, 20);
    }
  };

  /**
   * Manipulador para evento dragover - detecta potenciais alvos
   */
  const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    
    // Atualizar posição da imagem de arrasto, se estiver usando uma personalizada
    if (dragImage.value) {
      dragImage.value.style.left = `${event.clientX}px`;
      dragImage.value.style.top = `${event.clientY}px`;
    }
    
    // Encontrar a prateleira sob o cursor
    const shelfElement = findShelfElementUnderCursor(event.clientX, event.clientY);
    
    // Remover highlighting anterior
    document.querySelectorAll('.shelf-container').forEach(el => {
      el.classList.remove('layer-drop-target');
    });
    
    // Se encontrou uma prateleira válida, destacá-la
    if (shelfElement) {
      const shelfId = shelfElement.dataset.shelfId;
      
      // Não destacar a prateleira atual se for ela mesma
      if (shelfId && shelfId !== shelf.id) {
        shelfElement.classList.add('layer-drop-target');
        targetShelf.value = shelfId;
      } else {
        targetShelf.value = null;
      }
    } else {
      targetShelf.value = null;
    }
  };

  /**
   * Encontra o elemento da prateleira sob o cursor
   */
  const findShelfElementUnderCursor = (x: number, y: number): HTMLElement | null => {
    const elementsUnderCursor = document.elementsFromPoint(x, y);
    const shelfElement = elementsUnderCursor.find(el => 
      el.classList.contains('shelf-container')
    );
    
    return shelfElement as HTMLElement || null;
  };

  /**
   * Manipulador para evento drop - processa a soltura em uma prateleira
   */
  const handleDrop = (event: DragEvent) => {
    event.preventDefault();
    
    // Verificar se temos uma prateleira de destino
    if (!targetShelf.value) return;
    
    // Emitir evento para transferir a layer
    emit('transfer-layer', {
      layer,
      segment,
      fromShelfId: shelf.id,
      toShelfId: targetShelf.value
    } as TransferData);
    
    // Limpar estado
    cleanup();
  };

  /**
   * Manipulador para evento dragend - limpa após o arrasto
   */
  const handleDragEnd = () => {
    cleanup();
  };

  /**
   * Limpa o estado e elementos após o arrasto
   */
  const cleanup = () => {
    // Remover ouvintes globais
    document.removeEventListener('dragover', handleDragOver);
    document.removeEventListener('drop', handleDrop);
    document.removeEventListener('dragend', handleDragEnd);
    
    // Remover classes de estilo
    document.querySelectorAll('.shelf-container').forEach(el => {
      el.classList.remove('layer-drop-target');
    });
    
    if (layerElement.value) {
      layerElement.value.classList.remove('layer-dragging');
    }
    
    // Remover imagem de arrasto
    if (dragImage.value && dragImage.value.parentNode) {
      dragImage.value.parentNode.removeChild(dragImage.value);
      dragImage.value = null;
    }
    
    // Resetar estado
    isDragging.value = false;
    originalShelf.value = null;
    targetShelf.value = null;
    layerElement.value = null;
  };

  return {
    isDragging,
    startDrag,
    cleanup
  };
}