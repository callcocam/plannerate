// useShelfDragCommon.js
import { ref } from 'vue';

/**
 * Composable que fornece funções e utilidades compartilhadas para
 * operações de arrasto de prateleira, tanto para mouse quanto para toque
 */
export default function useShelfDragCommon() {
    /**
     * Dispara um evento de diagnóstico personalizado
     * @param {string} eventName - Nome do evento
     * @param {object} detail - Detalhes do evento
     */
    const dispatchDragEvent = (eventName, detail) => {
        window.dispatchEvent(new CustomEvent(eventName, { detail }));
    };

    /**
     * Adiciona classe CSS 'potential-drop-target' a um elemento
     * @param {HTMLElement} element - Elemento alvo
     */
    const addPotentialDropTargetClass = (element) => {
        if (element) {
            element.classList.add('potential-drop-target');
        }
    };

    /**
     * Remove a classe 'potential-drop-target' de todos os elementos .section-item
     */
    const removePotentialDropTargetClasses = () => {
        document.querySelectorAll('.section-item').forEach(section => {
            section.classList.remove('potential-drop-target');
        });
    };

    /**
     * Prepara um elemento para arrasto aplicando classes CSS
     * @param {HTMLElement} element - Elemento da prateleira
     * @param {boolean} isDragHandle - Indica se o arrasto começou na alça
     */
    const prepareElementForDrag = (element, isDragHandle) => {
        if (element) {
            element.classList.add('shelf-dragging-active');
            if (isDragHandle) {
                element.classList.add('handle-dragging');
            }
        }
        document.body.classList.add('shelf-dragging-in-progress');
    };

    /**
     * Limpa um elemento após o arrasto
     * @param {HTMLElement} element - Elemento da prateleira
     */
    const cleanupElementAfterDrag = (element) => {
        document.body.classList.remove('shelf-dragging-in-progress');
        if (element) {
            element.classList.remove('shelf-dragging-active');
            element.classList.remove('handle-dragging');
            element.style.transform = '';
        }
        removePotentialDropTargetClasses();
    };

    /**
     * Encontra o elemento section-item sob um ponto (x,y)
     * @param {number} x - Coordenada X
     * @param {number} y - Coordenada Y
     * @param {string} currentSectionId - ID da seção atual
     * @returns {object} Resultado da busca com sectionElement e sectionId
     */
    const findSectionElementUnderPoint = (x, y, currentSectionId) => {
        const elementsUnderPoint = document.elementsFromPoint(x, y);
        const sectionElement:any = elementsUnderPoint.find(el =>
            el.classList.contains('section-item')
        );

        let targetSectionId = null;

        if (sectionElement) {
            const sectionId = sectionElement.dataset.sectionId;

            // Só considera como alvo se for uma seção diferente
            if (sectionId && sectionId !== currentSectionId) {
                targetSectionId = sectionId;
            }
        }

        return {
            sectionElement,
            targetSectionId
        };
    };

    return {
        dispatchDragEvent,
        addPotentialDropTargetClass,
        removePotentialDropTargetClasses,
        prepareElementForDrag,
        cleanupElementAfterDrag,
        findSectionElementUnderPoint
    };
}