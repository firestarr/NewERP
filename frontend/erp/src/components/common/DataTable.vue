<!-- src/components/common/DataTable.vue -->
<template>
    <div class="data-table-wrapper">
      <div v-if="isLoading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Loading data...
      </div>

      <div v-else-if="items.length === 0" class="empty-state">
        <div class="empty-icon">
          <i :class="emptyIcon"></i>
        </div>
        <h3>{{ emptyTitle }}</h3>
        <p>{{ emptyMessage }}</p>
      </div>

      <table v-else class="data-table">
        <thead>
          <tr>
            <th v-for="column in columns" :key="column.key"
                :class="{ sortable: column.sortable }"
                @click="column.sortable && sort(column.key)">
              {{ column.label }}
              <i v-if="column.sortable && sortKey === column.key"
                 :class="sortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
            </th>
            <th v-if="hasActions" class="actions-column">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in items" :key="keyField ? item[keyField] : index">
            <td v-for="column in columns" :key="column.key" :class="column.class">
              <template v-if="column.formatter">
                <span v-html="column.formatter(item[column.key], item)"></span>
              </template>
              <template v-else-if="column.template">
                <slot :name="column.template" :item="item" :value="item[column.key]"></slot>
              </template>
              <template v-else>
                {{ item[column.key] }}
              </template>
            </td>
            <td v-if="hasActions" class="actions-cell">
              <slot name="actions" :item="item" :index="index"></slot>
            </td>
          </tr>
        </tbody>
      </table>

      <slot name="footer"></slot>
    </div>
  </template>

  <script>
  export default {
    name: 'DataTable',
    props: {
      columns: {
        type: Array,
        required: true,
        // Expected format:
        // [
        //   { key: 'id', label: 'ID', sortable: true },
        //   { key: 'name', label: 'Name', sortable: true },
        //   { key: 'status', label: 'Status', formatter: (value) => `<span class="badge">${value}</span>` },
        //   { key: 'custom', label: 'Custom', template: 'custom-template' }
        // ]
      },
      items: {
        type: Array,
        required: true
      },
      isLoading: {
        type: Boolean,
        default: false
      },
      keyField: {
        type: String,
        default: 'id'
      },
      emptyIcon: {
        type: String,
        default: 'fas fa-folder-open'
      },
      emptyTitle: {
        type: String,
        default: 'Tidak ada data'
      },
      emptyMessage: {
        type: String,
        default: 'Tidak ada data yang tersedia untuk ditampilkan.'
      },
      initialSortKey: {
        type: String,
        default: ''
      },
      initialSortOrder: {
        type: String,
        default: 'asc'
      }
    },
    data() {
      return {
        sortKey: this.initialSortKey,
        sortOrder: this.initialSortOrder
      };
    },
    computed: {
      hasActions() {
        return !!this.$slots.actions;
      }
    },
    methods: {
      sort(key) {
        if (this.sortKey === key) {
          this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
          this.sortKey = key;
          this.sortOrder = 'asc';
        }

        this.$emit('sort', { key, order: this.sortOrder });
      }
    }
  };
  </script>

  <style scoped>
  .data-table-wrapper {
    width: 100%;
    overflow-x: auto;
  }

  .data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
  }

  .data-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-200);
    background-color: var(--gray-50);
    font-weight: 500;
    color: var(--gray-600);
  }

  .data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
    color: var(--gray-800);
    vertical-align: middle;
  }

  .data-table tr:hover td {
    background-color: var(--gray-50);
  }

  .actions-column {
    width: 1%;
    white-space: nowrap;
  }

  .actions-cell {
    text-align: right;
  }

  .sortable {
    cursor: pointer;
    user-select: none;
  }

  .sortable:hover {
    background-color: var(--gray-100);
  }

  .loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 3rem 0;
    color: var(--gray-500);
    font-size: 0.875rem;
  }

  .loading-indicator i {
    margin-right: 0.5rem;
    animation: spin 1s linear infinite;
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 0;
    text-align: center;
  }

  .empty-icon {
    font-size: 2.5rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    font-size: 1.125rem;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
  }

  .empty-state p {
    color: var(--gray-500);
    max-width: 24rem;
  }

  @keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }
  </style>
