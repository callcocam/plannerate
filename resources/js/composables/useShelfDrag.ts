import { ref, onBeforeUnmount } from 'vue';

export default function useShelfDrag({
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
}: any) {
    const onMouseDown = (event: MouseEvent) => {
        // Ignora o botão direito (usado para o menu de contexto)
        if (event.button === 2) return;

        event.preventDefault();
        startDrag(event.clientY);

        // Adiciona listeners para movimento e para quando soltar o mouse
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    };

    const onTouchStart = (event: TouchEvent) => {
        event.preventDefault();
        if (event.touches && event.touches[0]) {
            startDrag(event.touches[0].clientY);
        }

        // Adiciona listeners para touch events
        document.addEventListener('touchmove', onTouchMove, { passive: false });
        document.addEventListener('touchend', onTouchEnd);
    };

    const startDrag = (clientY: number) => {
        isDragging.value = true;
        startY.value = clientY;
        originalPosition.value = shelfPosition.value;

        // Adiciona classe ao body para prevenir seleção de texto
        document.body.classList.add('shelf-dragging');
    };

    const onMouseMove = (event: MouseEvent) => {
        if (!isDragging.value) return;
        event.preventDefault();
        updateShelfPosition(event.clientY);
    };

    const onTouchMove = (event: TouchEvent) => {
        if (!isDragging.value) return;
        event.preventDefault(); // Previne scroll durante arraste
        if (event.touches && event.touches[0]) {
            updateShelfPosition(event.touches[0].clientY);
        }
    };

    // Ajustado para trabalhar com a base da prateleira mas mantendo compatibilidade
    const updateShelfPosition = (clientY: number) => {
        // Inverte a direção do movimento usando a diferença de posição do mouse
        const deltaY = (startY.value - clientY) / scaleFactor.value;
        // Calcula a nova posição
        let newPosition = parseFloat(originalPosition.value) + deltaY;
        // Limita dentro da gôndola
        newPosition = Math.max(props.gondola.base_height, Math.min(newPosition, props.gondola.height - props.gondola.shelf_height));
        // Atualiza a posição da prateleira no estado local
        shelfPosition.value = newPosition;
    };

    const onMouseUp = () => {
        finishDrag();
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    };

    const onTouchEnd = () => {
        finishDrag();
        document.removeEventListener('touchmove', onTouchMove);
        document.removeEventListener('touchend', onTouchEnd);
    };

    const finishDrag = () => {
        if (isDragging.value) {
            isDragging.value = false;
            document.body.classList.remove('shelf-dragging');

            // Alinha com o furo mais próximo quando termina o arraste
            alignShelf();
        }
    };

    // Remove todos os listeners quando o componente for desmontado
    onBeforeUnmount(() => {
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
        document.removeEventListener('touchmove', onTouchMove);
        document.removeEventListener('touchend', onTouchEnd);
        document.body.classList.remove('shelf-dragging');

        // Limpa timeout de drag
        if (dragLeaveTimeout.value) {
            clearTimeout(dragLeaveTimeout.value);
            dragLeaveTimeout.value = null;
        }
    });

    // Funções para drop de produtos - agora permitindo drop na shelf-container inteira
    const onDragOver = (event: DragEvent) => {
        event.preventDefault();

        // Se houver um timeout pendente para esconder o indicador, cancele-o
        if (dragLeaveTimeout.value) {
            clearTimeout(dragLeaveTimeout.value);
            dragLeaveTimeout.value = null;
        }

        // Garanta que a área de drop esteja ativa
        isDropTarget.value = true;

        // Impedir propagação para evitar eventos em elementos filhos
        event.stopPropagation();
    };

    const onDragLeave = (event: DragEvent) => {
        // Verifique se o mouse realmente saiu do elemento e não apenas entrou em um filho
        if (event.currentTarget && (event.currentTarget as HTMLElement).contains(event.relatedTarget as Node)) {
            return; // Mouse apenas passou para um elemento filho, ignore
        }

        // Use um timeout curto para evitar piscar quando o mouse move entre elementos
        dragLeaveTimeout.value = setTimeout(() => {
            isDropTarget.value = false;
            dragLeaveTimeout.value = null;
        }, 100);
    };

    // Modificado para organizar os produtos da esquerda para a direita
    const onDrop = (event: DragEvent) => {
        event.preventDefault();
        isDropTarget.value = false;
console.log('Drop event triggered');
        // Limpa o timeout se existir
        if (dragLeaveTimeout.value) {
            clearTimeout(dragLeaveTimeout.value);
            dragLeaveTimeout.value = null;
        }

        try {
            const jsonData = event.dataTransfer?.getData('application/json');
            if (jsonData) {
                const product = JSON.parse(jsonData);

                // Create a new segment with all necessary properties
                const newSegment = {
                    id: `segment-${Date.now()}`,
                    width: parseInt(props.shelf.section?.width),
                    ordering: (props.shelf.segments?.length || 0) + 1,
                    quantity: 1,
                    spacing: 0,
                    position: 0,
                    preserveState: false,
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

    // Abre o modal de configurações de produto quando clicado na prateleira
    const handleShelfClick = (event: MouseEvent) => {
        // Previne a abertura do modal durante o arraste
        if (isDragging.value) return;

        // Emite o evento selectShelf quando a prateleira é clicada
        emit('selectShelf', props.shelf);

        // Abre o modal apenas se tiver produto ou durante o drop
        if (props.shelf.product) {
            openProductSettings.value = true;
        }
    };

    // Remove o produto da prateleira
    function removeProduct() {
        const updatedShelf = {
            ...props.shelf,
            product: null,
            quantity: null,
        };

        // Preserva a posição original da prateleira
        emit('update:shelf', updatedShelf);
        openProductSettings.value = false;
    }

    return {
        onMouseDown,
        onTouchStart,
        onDragOver,
        onDragLeave,
        onDrop,
        handleShelfClick,
        removeProduct,
    };
}
