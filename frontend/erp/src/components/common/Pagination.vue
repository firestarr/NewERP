<!-- src/components/common/Pagination.vue -->
<template>
    <div class="pagination">
      <div class="pagination-info">
        Page {{ from }} to {{ to }} From {{ total }} Item
      </div>
      <div class="pagination-controls">
        <button
          class="pagination-btn"
          :disabled="currentPage === 1"
          @click="onPageChange(currentPage - 1)"
        >
          <i class="fas fa-chevron-left"></i>
        </button>

        <template v-for="page in displayedPages" :key="page">
          <button
            v-if="page !== '...'"
            class="pagination-btn"
            :class="{ active: page === currentPage }"
            @click="onPageChange(page)"
          >
            {{ page }}
          </button>
          <span v-else class="pagination-ellipsis">...</span>
        </template>

        <button
          class="pagination-btn"
          :disabled="currentPage === totalPages"
          @click="onPageChange(currentPage + 1)"
        >
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>
  </template>

  <script>
  export default {
    name: 'PaginationComponent',
    props: {
      currentPage: {
        type: Number,
        required: true
      },
      totalPages: {
        type: Number,
        required: true
      },
      from: {
        type: Number,
        required: true
      },
      to: {
        type: Number,
        required: true
      },
      total: {
        type: Number,
        required: true
      }
    },
    computed: {
      displayedPages() {
        const total = this.totalPages;
        const current = this.currentPage;
        const pages = [];

        if (total <= 7) {
          // Show all pages if 7 or fewer
          for (let i = 1; i <= total; i++) {
            pages.push(i);
          }
        } else {
          // Always include first page
          pages.push(1);

          // Show ellipsis if current page is more than 3
          if (current > 3) {
            pages.push('...');
          }

          // Add pages around current page
          const startPage = Math.max(2, current - 1);
          const endPage = Math.min(total - 1, current + 1);

          for (let i = startPage; i <= endPage; i++) {
            pages.push(i);
          }

          // Show ellipsis if current page is less than total - 2
          if (current < total - 2) {
            pages.push('...');
          }

          // Always include last page
          if (total > 1) {
            pages.push(total);
          }
        }

        return pages;
      }
    },
    methods: {
      onPageChange(page) {
        this.$emit('page-changed', page);
      }
    }
  };
  </script>

  <style scoped>
  .pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-top: 1px solid var(--gray-200);
  }

  .pagination-info {
    color: var(--gray-500);
    font-size: 0.875rem;
  }

  .pagination-controls {
    display: flex;
    gap: 0.25rem;
  }

  .pagination-btn {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    background: none;
    border: 1px solid var(--gray-200);
    color: var(--gray-500);
    cursor: pointer;
  }

  .pagination-btn:hover:not(:disabled) {
    background-color: var(--gray-100);
    color: var(--gray-800);
  }

  .pagination-btn.active {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
  }

  .pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .pagination-ellipsis {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 2rem;
    height: 2rem;
    color: var(--gray-500);
  }

  @media (max-width: 640px) {
    .pagination {
      flex-direction: column;
      gap: 1rem;
    }
  }
  </style>
