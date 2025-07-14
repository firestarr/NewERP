<template>
  <div class="job-tickets-list">
    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">Job Tickets</h1>
        <p class="page-description">Manage and track job ticket progress</p>
      </div>
      <div class="header-actions">
        <button @click="exportData" class="btn btn-outline" :disabled="isLoading">
          <i class="fas fa-download"></i> Export
        </button>
        <button @click="refreshData" class="btn btn-primary" :disabled="isLoading">
          <i class="fas fa-sync" :class="{ 'fa-spin': isLoading }"></i> Refresh
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid" v-if="statistics">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-ticket-alt"></i>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ statistics.total_tickets }}</div>
          <div class="stat-label">Total Tickets</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ statistics.completed_tickets }}</div>
          <div class="stat-label">Completed</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ statistics.pending_tickets }}</div>
          <div class="stat-label">Pending</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-percentage"></i>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ statistics.overall_completion_rate }}%</div>
          <div class="stat-label">Completion Rate</div>
        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="filters-section">
      <SearchFilter
        v-model="searchQuery"
        :placeholder="'Search tickets, items, customers...'"
        @search="handleSearch"
        @clear="clearSearch"
      />

      <div class="filter-controls">
        <select v-model="sortBy" @change="handleSort" class="form-select">
          <option value="fgrn_no">Sort by FGRN No</option>
          <option value="item">Sort by Item</option>
          <option value="customer">Sort by Customer</option>
        </select>

        <select v-model="sortOrder" @change="handleSort" class="form-select">
          <option value="desc">Descending</option>
          <option value="asc">Ascending</option>
        </select>
      </div>
    </div>

    <!-- Data Table -->
    <div class="table-container">
      <DataTable
        :columns="tableColumns"
        :items="jobTickets"
        :is-loading="isLoading"
        :key-field="'ticket_id'"
        :empty-title="'No Job Tickets Found'"
        :empty-message="'No job tickets available. Start by creating production orders.'"
        empty-icon="fas fa-ticket-alt"
        @sort="handleTableSort"
      >
        <!-- Custom No Template -->
        <template #no="{ item }">
          {{ getSequentialNumber(item) }}
        </template>

        <!-- Custom Status Template -->
        <template #status="{ item }">
          <span :class="getStatusClass(item)">
            {{ getStatusText(item) }}
          </span>
        </template>

        <!-- Actions Template -->
        <template #actions="{ item }">
          <div class="action-buttons">
            <button
              @click="viewDetail(item.ticket_id)"
              class="btn btn-sm btn-outline"
              title="View Details"
            >
              <i class="fas fa-eye"></i>
            </button>
            <button
              @click="printTicket(item.ticket_id)"
              class="btn btn-sm btn-outline"
              title="Print Ticket"
            >
              <i class="fas fa-print"></i>
            </button>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Pagination -->
    <PaginationComponent
      v-if="pagination.total > 0"
      :current-page="pagination.current_page"
      :total-pages="pagination.last_page"
      :total-items="pagination.total"
      :per-page="pagination.per_page"
      :from="pagination.from"
      :to="pagination.to"
      @page-changed="handlePageChange"
      @per-page-changed="handlePerPageChange"
    />
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'JobTicketList',
  data() {
    return {
      jobTickets: [],
      statistics: null,
      isLoading: false,
      searchQuery: '',
      sortBy: 'fgrn_no',
      sortOrder: 'desc',
      pagination: {
        current_page: 1,
        per_page: 15,
        total: 0,
        last_page: 1,
        from: 0,
        to: 0
      },
      tableColumns: [
        { key: 'no', label: 'No', template: 'no', sortable: false },
        { key: 'fgrn_no', label: 'FGRN No', sortable: true },
        { key: 'item', label: 'Item', sortable: true },
        { key: 'customer', label: 'Customer', sortable: true },
        { key: 'status', label: 'Status', template: 'status', sortable: false }
      ]
    };
  },
  created() {
    this.fetchJobTickets();
    this.fetchStatistics();
  },
  methods: {
    async fetchJobTickets() {
      this.isLoading = true;
      try {
        const params = {
          page: this.pagination.current_page,
          per_page: this.pagination.per_page,
          search: this.searchQuery,
          sort_by: this.sortBy,
          sort_order: this.sortOrder
        };

        const response = await axios.get('/manufacturing/job-tickets', { params });

        if (response.data.success) {
          this.jobTickets = response.data.data.data;
          this.pagination = response.data.data;
        } else {
          this.$toast.error(response.data.message || 'Failed to fetch job tickets');
        }
      } catch (error) {
        console.error('Error fetching job tickets:', error);
        this.$toast.error('Failed to fetch job tickets');
      } finally {
        this.isLoading = false;
      }
    },

    async fetchStatistics() {
      try {
        const response = await axios.get('/manufacturing/job-tickets/statistics');
        if (response.data.success) {
          this.statistics = response.data.data;
        }
      } catch (error) {
        console.error('Error fetching statistics:', error);
      }
    },

    handleSearch(query) {
      this.searchQuery = query;
      this.pagination.current_page = 1;
      this.fetchJobTickets();
    },

    clearSearch() {
      this.searchQuery = '';
      this.pagination.current_page = 1;
      this.fetchJobTickets();
    },

    handleSort() {
      this.pagination.current_page = 1;
      this.fetchJobTickets();
    },

    handleTableSort(sortData) {
      this.sortBy = sortData.key;
      this.sortOrder = sortData.order;
      this.handleSort();
    },

    handlePageChange(page) {
      this.pagination.current_page = page;
      this.fetchJobTickets();
    },

    handlePerPageChange(perPage) {
      this.pagination.per_page = perPage;
      this.pagination.current_page = 1;
      this.fetchJobTickets();
    },

    refreshData() {
      this.fetchJobTickets();
      this.fetchStatistics();
    },

    viewDetail(ticketId) {
      this.$router.push({
        name: 'JobTicketDetail',
        params: { id: ticketId }
      });
    },

    printTicket(ticketId) {
      this.$router.push({
        name: 'JobTicketPrint',
        params: { id: ticketId }
      });
    },

    async exportData() {
      try {
        this.isLoading = true;
        const response = await axios.post('/manufacturing/job-tickets/export', {
          search: this.searchQuery
        });

        if (response.data.success) {
          // Handle export data - could trigger download or show in new tab
          this.$toast.success('Export completed successfully');
          console.log('Export data:', response.data.data);
        }
      } catch (error) {
        console.error('Error exporting data:', error);
        this.$toast.error('Failed to export data');
      } finally {
        this.isLoading = false;
      }
    },

    getSequentialNumber(item) {
      const index = this.jobTickets.findIndex(ticket => ticket.ticket_id === item.ticket_id);
      return ((this.pagination.current_page - 1) * this.pagination.per_page) + index + 1;
    },

    getStatusClass(item) {
      const completion = this.getCompletionPercentage(item);
      if (completion >= 100) return 'status-badge status-completed';
      if (completion > 0) return 'status-badge status-in-progress';
      return 'status-badge status-pending';
    },

    getStatusText(item) {
      const completion = this.getCompletionPercentage(item);
      if (completion >= 100) return 'Completed';
      if (completion > 0) return 'In Progress';
      return 'Pending';
    },

    getCompletionPercentage(item) {
      if (!item.qty_jo || item.qty_jo <= 0) return 0;
      const percentage = (parseFloat(item.qty_completed) / parseFloat(item.qty_jo)) * 100;
      return Math.min(Math.round(percentage), 100);
    },

    formatDate(date) {
      if (!date) return '';
      const d = new Date(date);
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }
  }
};
</script>

<style scoped>
.job-tickets-list {
  padding: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.header-content h1 {
  margin: 0 0 0.5rem 0;
  color: var(--gray-900);
}

.header-content p {
  margin: 0;
  color: var(--gray-600);
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  background: var(--blue-50);
  color: var(--blue-600);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.stat-number {
  font-size: 1.875rem;
  font-weight: 600;
  color: var(--gray-900);
  line-height: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: var(--gray-600);
  margin-top: 0.25rem;
}

.filters-section {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.filter-controls {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  margin-left: auto;
}

.table-container {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 1.5rem;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.status-completed {
  background-color: var(--green-100);
  color: var(--green-800);
}

.status-in-progress {
  background-color: var(--yellow-100);
  color: var(--yellow-800);
}

.status-pending {
  background-color: var(--gray-100);
  color: var(--gray-800);
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background-color: var(--blue-600);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: var(--blue-700);
}

.btn-outline {
  background-color: white;
  color: var(--gray-700);
  border: 1px solid var(--gray-300);
}

.btn-outline:hover:not(:disabled) {
  background-color: var(--gray-50);
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
}

.form-select {
  padding: 0.5rem;
  border: 1px solid var(--gray-300);
  border-radius: 6px;
  font-size: 0.875rem;
  background-color: white;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 1rem;
  }

  .header-actions {
    width: 100%;
    justify-content: flex-end;
  }

  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-controls {
    margin-left: 0;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
