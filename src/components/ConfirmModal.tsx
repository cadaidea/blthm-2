import { useEffect, useState } from 'react';
import { create } from 'zustand';
import { I } from './ui';

// ============================================
// STORE GLOBAL PARA MODALES DE CONFIRMACIÓN
// ============================================

interface ConfirmModalState {
  isOpen: boolean;
  title: string;
  message: string;
  confirmText: string;
  cancelText: string;
  variant: 'danger' | 'warning' | 'info';
  onConfirm: () => void;
  onCancel: () => void;
}

interface ConfirmModalStore {
  modal: ConfirmModalState | null;
  show: (options: {
    title: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'danger' | 'warning' | 'info';
    onConfirm: () => void;
    onCancel?: () => void;
  }) => void;
  hide: () => void;
}

export const useConfirmModal = create<ConfirmModalStore>((set) => ({
  modal: null,
  show: (options) => {
    set({
      modal: {
        isOpen: true,
        title: options.title,
        message: options.message,
        confirmText: options.confirmText || 'Confirmar',
        cancelText: options.cancelText || 'Cancelar',
        variant: options.variant || 'danger',
        onConfirm: options.onConfirm,
        onCancel: options.onCancel || (() => {}),
      },
    });
  },
  hide: () => set({ modal: null }),
}));

// ============================================
// COMPONENTE DEL MODAL
// ============================================

export function ConfirmModalHost() {
  const { modal, hide } = useConfirmModal();

  useEffect(() => {
    if (modal) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [modal]);

  if (!modal) return null;

  const variantStyles = {
    danger: {
      icon: 'alert',
      iconColor: 'text-bad',
      iconBg: 'bg-badbg',
      buttonBg: 'bg-bad hover:bg-bad/90',
    },
    warning: {
      icon: 'alert',
      iconColor: 'text-warn',
      iconBg: 'bg-warnbg',
      buttonBg: 'bg-warn hover:bg-warn/90',
    },
    info: {
      icon: 'spark',
      iconColor: 'text-ink',
      iconBg: 'bg-paper2',
      buttonBg: 'bg-ink hover:bg-ink/90',
    },
  };

  const styles = variantStyles[modal.variant];

  const handleConfirm = () => {
    modal.onConfirm();
    hide();
  };

  const handleCancel = () => {
    modal.onCancel();
    hide();
  };

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-ink/60 backdrop-blur-sm anim-fadeIn"
        onClick={handleCancel}
      />

      {/* Modal */}
      <div className="relative bg-card rounded-lg shadow-2xl max-w-md w-full anim-slideUp">
        {/* Icon */}
        <div className="flex justify-center -mt-6">
          <div className={`w-12 h-12 rounded-full ${styles.iconBg} flex items-center justify-center`}>
            <I n={styles.icon as any} s={24} className={styles.iconColor} />
          </div>
        </div>

        {/* Content */}
        <div className="p-6 pt-4 text-center">
          <h3 className="text-lg font-bold text-ink mb-2">
            {modal.title}
          </h3>
          <p className="text-sm text-ink2 leading-relaxed">
            {modal.message}
          </p>
        </div>

        {/* Actions */}
        <div className="flex gap-3 p-6 pt-0">
          <button
            onClick={handleCancel}
            className="flex-1 px-4 py-2.5 border border-line text-ink2 font-medium rounded-lg hover:bg-paper2 transition-colors"
          >
            {modal.cancelText}
          </button>
          <button
            onClick={handleConfirm}
            className={`flex-1 px-4 py-2.5 text-paper font-medium rounded-lg transition-colors ${styles.buttonBg}`}
          >
            {modal.confirmText}
          </button>
        </div>
      </div>
    </div>
  );
}

// ============================================
// HELPER FUNCTIONS
// ============================================

export const confirm = {
  delete: (itemName: string, onConfirm: () => void) => {
    useConfirmModal.getState().show({
      title: 'Eliminar elemento',
      message: `¿Estás seguro de que deseas eliminar "${itemName}"? Esta acción no se puede deshacer.`,
      confirmText: 'Eliminar',
      cancelText: 'Cancelar',
      variant: 'danger',
      onConfirm,
    });
  },

  deleteProduct: (productName: string, onConfirm: () => void) => {
    useConfirmModal.getState().show({
      title: 'Eliminar producto',
      message: `¿Estás seguro de que deseas eliminar el producto "${productName}"? Se eliminarán también sus imágenes y variantes.`,
      confirmText: 'Eliminar producto',
      cancelText: 'Cancelar',
      variant: 'danger',
      onConfirm,
    });
  },

  deleteCustomer: (customerName: string, onConfirm: () => void) => {
    useConfirmModal.getState().show({
      title: 'Eliminar cliente',
      message: `¿Estás seguro de que deseas eliminar al cliente "${customerName}"? Se eliminarán todos sus datos y pedidos asociados.`,
      confirmText: 'Eliminar cliente',
      cancelText: 'Cancelar',
      variant: 'danger',
      onConfirm,
    });
  },

  deleteOrder: (orderCode: string, onConfirm: () => void) => {
    useConfirmModal.getState().show({
      title: 'Eliminar pedido',
      message: `¿Estás seguro de que deseas eliminar el pedido "${orderCode}"? Esta acción no se puede deshacer.`,
      confirmText: 'Eliminar pedido',
      cancelText: 'Cancelar',
      variant: 'danger',
      onConfirm,
    });
  },

  deleteArticle: (articleTitle: string, onConfirm: () => void) => {
    useConfirmModal.getState().show({
      title: 'Eliminar artículo',
      message: `¿Estás seguro de que deseas eliminar el artículo "${articleTitle}"? Se eliminará del blog permanentemente.`,
      confirmText: 'Eliminar artículo',
      cancelText: 'Cancelar',
      variant: 'danger',
      onConfirm,
    });
  },

  deleteCategory: (categoryName: string, onConfirm: () => void) => {
    useConfirmModal.getState().show({
      title: 'Eliminar categoría',
      message: `¿Estás seguro de que deseas eliminar la categoría "${categoryName}"? Los productos asociados quedarán sin categoría.`,
      confirmText: 'Eliminar categoría',
      cancelText: 'Cancelar',
      variant: 'warning',
      onConfirm,
    });
  },

  unpublish: (itemName: string, onConfirm: () => void) => {
    useConfirmModal.getState().show({
      title: 'Despublicar elemento',
      message: `¿Estás seguro de que deseas despublicar "${itemName}"? No será visible en la tienda.`,
      confirmText: 'Despublicar',
      cancelText: 'Cancelar',
      variant: 'warning',
      onConfirm,
    });
  },

  cancelOrder: (orderCode: string, onConfirm: () => void) => {
    useConfirmModal.getState().show({
      title: 'Cancelar pedido',
      message: `¿Estás seguro de que deseas cancelar el pedido "${orderCode}"? El cliente será notificado.`,
      confirmText: 'Cancelar pedido',
      cancelText: 'No cancelar',
      variant: 'warning',
      onConfirm,
    });
  },

  generic: (title: string, message: string, onConfirm: () => void, variant: 'danger' | 'warning' | 'info' = 'info') => {
    useConfirmModal.getState().show({
      title,
      message,
      variant,
      onConfirm,
    });
  },
};
