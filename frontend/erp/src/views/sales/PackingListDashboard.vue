<template>
  <div class="packing-dashboard">
    <!-- Header -->
    <div class="dashboard-header">
      <div class="header-content">
        <h1 class="dashboard-title">
          <i class="fas fa-tachometer-alt"></i>
          Packing Dashboard
        </h1>
        <p class="dashboard-subtitle">Overview of packing operations and performance</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-outline" @click="refreshData">
          <i class="fas fa-sync-alt" :class="{ 'fa-spin': refreshing }"></i>
          Refresh
        </button>
        <button class="btn btn-primary" @click="$router.push('/packing-lists/create')">
          <i class="fas fa-plus"></i>
          New Packing List
        </button>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-section">
      <div class="stats-grid">
        <div class="stat-card primary">
          <div class="stat-icon">
            <i class="fas fa-boxes"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{{ stats.total_packing_lists || 0 }}</div>
            <div class="stat-label">Total Packing Lists</div>
            <div class="stat-change positive" v-if="stats.change_total > 0">
              <i class="fas fa-arrow-up"></i>
              +{{ stats.change_total }} this week
            </div>
          </div>
        </div>

        <div class="stat-card warning">
          <div class="stat-icon">
            <i class="fas fa-clock"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{{ stats.in_progress || 0 }}</div>
            <div class="stat-label">In Progress</div>
            <div class="stat-subtext">Currently being packed</div>
          </div>
        </div>

        <div class="stat-card success">
          <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{{ stats.completed || 0 }}</div>
            <div class="stat-label">Completed Today</div>
            <div class="stat-subtext">Ready for shipping</div>
          </div>
        </div>

        <div class="stat-card info">
          <div class="stat-icon">
            <i class="fas fa-shipping-fast"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{{ stats.shipped || 0 }}</div>
            <div class="stat-label">Shipped Today</div>
            <div class="stat-subtext">Packages sent out</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Performance Metrics -->
    <div class="metrics-section">
      <div class="metrics-grid">
        <div class="metric-card">
          <h3 class="metric-title">
            <i class="fas fa-weight-hanging"></i>
            Total Weight Packed
          </h3>
          <div class="metric-value">
            {{ formatWeight(stats.total_weight) }}
          </div>
          <div class="metric-progress">
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: '75%' }"></div>
            </div>
            <span class="progress-text">75% of monthly target</span>
          </div>
        </div>

        <div class="metric-card">
          <h3 class="metric-title">
            <i class="fas fa-cube"></i>
            Total Volume Packed
          </h3>
          <div class="metric-value">
            {{ formatVolume(stats.total_volume) }}
          </div>
          <div class="metric-progress">
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: '68%' }"></div>
            </div>
            <span class="progress-text">68% of monthly target</span>
          </div>
        </div>

        <div class="metric-card">
          <h3 class="metric-title">
            <i class="fas fa-archive"></i>
            Total Packages
          </h3>
          <div class="metric-value">
            {{ stats.total_packages || 0 }}
          </div>
          <div class="metric-progress">
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: '82%' }"></div>
            </div>
            <span class="progress-text">82% of monthly target</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Overview -->
    <div class="overview-section">
      <div class="overview-grid">
        <!-- Status Distribution -->
        <div class="overview-card">
          <h3 class="card-title">
            <i class="fas fa-chart-pie"></i>
            Status Distribution
          </h3>
          <div class="status-chart">
            <div class="status-item" v-for="status in statusDistribution" :key="status.status">
              <div class="status-bar">
                <div class="status-label">{{ status.status }}</div>
                <div class="progress-container">
                  <div class="progress-bar">
                    <div 
                      class="progress-fill" 
                      :class="getStatusClass(status.status)"
                      :style="{ width: status.percentage + '%' }"
                    ></div>
                  </div>
                  <span class="status-count">{{ status.count }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="overview-card">
          <h3 class="card-title">
            <i class="fas fa-history"></i>
            Recent Activity
          </h3>
          <div class="activity-list">
            <div v-for="activity in recentActivity" :key="activity.id" class="activity-item">
              <div class="activity-icon" :class="getActivityIconClass(activity.type)">
                <i class="fas" :class="getActivityIcon(activity.type)"></i>
              </div>
              <div class="activity-content">
                <div class="activity-title">{{ activity.title }}</div>
                <div class="activity-description">{{ activity.description }}</div>
                <div class="activity-time">{{ formatRelativeTime(activity.created_at) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions & Pending Items -->
    <div class="actions-section">
      <div class="actions-grid">
        <!-- Quick Actions -->
        <div class="action-card">
          <h3 class="card-title">
            <i class="fas fa-bolt"></i>
            Quick Actions
          </h3>
          <div class="action-buttons">
            <button class="action-btn" @click="$router.push('/packing-lists/create')">
              <i class="fas fa-plus"></i>
              <span>Create New Packing List</span>
            </button>
            <button class="action-btn" @click="$router.push('/packing-lists?status=In Progress')">
              <i class="fas fa-tasks"></i>
              <span>View In Progress</span>
            </button>
            <button class="action-btn" @click="$router.push('/deliveries/available-for-packing')">
              <i class="fas fa-truck"></i>
              <span>Available Deliveries</span>
            </button>
            <button class="action-btn" @click="openBulkShipping">
              <i class="fas fa-shipping-fast"></i>
              <span>Bulk Ship Completed</span>
            </button>
          </div>
        </div>

        <!-- Pending Items -->
        <div class="action-card">
          <h3 class="card-title">
            <i class="fas fa-exclamation-triangle"></i>
            Items Requiring Attention
          </h3>
          <div class="pending-list">
            <div v-for="item in pendingItems" :key="item.id" class="pending-item">
              <div class="pending-icon" :class="item.urgency">
                <i class="fas" :class="item.icon"></i>
              </div>
              <div class="pending-content">
                <div class="pending-title">{{ item.title }}</div>
                <div class="pending-description">{{ item.description }}</div>
              </div>
              <button class="pending-action" @click="handlePendingAction(item)">
                {{ item.action }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-content">
        <div class="spinner"></div>
        <p>Loading dashboard data...</p>
      </div>
    </div>

    <!-- Bulk Shipping Modal -->
    <div v-if="showBulkModal" class="modal-overlay" @click="showBulkModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>Bulk Ship Completed Packing Lists</h2>
          <button class="btn-close" @click="showBulkModal = false">×</button>
        </div>
        <div class="modal-body">
          <p>Select packing lists to mark as shipped:</p>
          <div class="bulk-list">
            <div v-for="pl in completedPackingLists" :key="pl.packing_list_id" class="bulk-item">
              <label class="checkbox-label">
                <input 
                  type="checkbox" 
                  v-model="selectedForShipping" 
                  :value="pl.packing_list_id"
                >
                <span class="checkmark"></span>
                {{ pl.packing_list_number }} - {{ pl.customer?.name }}
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showBulkModal = false">Cancel</button>
          <button 
            class="btn btn-primary" 
            @click="bulkShip" 
            :disabled="selectedForShipping.length === 0 || bulkShipping"
          >
            <span v-if="bulkShipping">Shipping...</span>
            <span v-else>Ship {{ selectedForShipping.length }} Items</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'PackingListDashboard',
  setup() {
    const loading = ref(false)
    const refreshing = ref(false)
    const showBulkModal = ref(false)
    const bulkShipping = ref(false)
    const selectedForShipping = ref([])
    
    const stats = reactive({
      total_packing_lists: 0,
      draft: 0,
      in_progress: 0,
      completed: 0,
      shipped: 0,
      total_weight: 0,
      total_volume: 0,
      total_packages: 0,
      change_total: 0
    })

    const recentActivity = ref([])
    const pendingItems = ref([])
    const completedPackingLists = ref([])

    // Computed properties
    const statusDistribution = computed(() => {
      const total = stats.total_packing_lists || 1
      return [
        { status: 'Draft', count: stats.draft, percentage: (stats.draft / total) * 100 },
        { status: 'In Progress', count: stats.in_progress, percentage: (stats.in_progress / total) * 100 },
        { status: 'Completed', count: stats.completed, percentage: (stats.completed / total) * 100 },
        { status: 'Shipped', count: stats.shipped, percentage: (stats.shipped / total) * 100 }
      ]
    })

    onMounted(() => {
      loadDashboardData()
    })

    // Methods
    const loadDashboardData = async () => {
      loading.value = true
      try {
        await Promise.all([
          loadStats(),
          loadRecentActivity(),
          loadPendingItems(),
          loadCompletedPackingLists()
        ])
      } catch (error) {
        console.error('Error loading dashboard data:', error)
      } finally {
        loading.value = false
      }
    }

    const loadStats = async () => {
      try {
        const response = await axios.get('/api/sales/packing-lists-progress')
        Object.assign(stats, response.data.summary)
      } catch (error) {
        console.error('Error loading stats:', error)
      }
    }

    const loadRecentActivity = async () => {
      try {
        const response = await axios.get('/api/sales/packing-lists?limit=10')
        recentActivity.value = response.data.data.map(pl => ({
          id: pl.packing_list_id,
          type: pl.status.toLowerCase().replace(' ', '_'),
          title: `${pl.packing_list_number}`,
          description: `${pl.status} - ${pl.customer?.name}`,
          created_at: pl.created_at || pl.packing_date
        }))
      } catch (error) {
        console.error('Error loading recent activity:', error)
      }
    }

    const loadPendingItems = async () => {
      try {
        // Mock data - replace with actual API calls
        pendingItems.value = [
          {
            id: 1,
            icon: 'fa-clock',
            urgency: 'high',
            title: 'Overdue Packing Lists',
            description: '3 packing lists are overdue for completion',
            action: 'Review'
          },
          {
            id: 2,
            icon: 'fa-exclamation',
            urgency: 'medium',
            title: 'Low Stock Items',
            description: '5 items have insufficient stock for packing',
            action: 'Check Stock'
          },
          {
            id: 3,
            icon: 'fa-truck',
            urgency: 'low',
            title: 'Available Deliveries',
            description: '12 deliveries are ready for packing',
            action: 'Create Lists'
          }
        ]
      } catch (error) {
        console.error('Error loading pending items:', error)
      }
    }

    const loadCompletedPackingLists = async () => {
      try {
        const response = await axios.get('/api/sales/packing-lists?status=Completed')
        completedPackingLists.value = response.data.data
      } catch (error) {
        console.error('Error loading completed packing lists:', error)
      }
    }

    const refreshData = async () => {
      refreshing.value = true
      try {
        await loadDashboardData()
      } finally {
        refreshing.value = false
      }
    }

    const openBulkShipping = () => {
      loadCompletedPackingLists()
      showBulkModal.value = true
      selectedForShipping.value = []
    }

    const bulkShip = async () => {
      if (selectedForShipping.value.length === 0) return

      bulkShipping.value = true
      try {
        await axios.post('/api/sales/packing-lists/bulk-ship', {
          packing_list_ids: selectedForShipping.value
        })
        
        alert(`Successfully shipped ${selectedForShipping.value.length} packing lists!`)
        showBulkModal.value = false
        selectedForShipping.value = []
        await loadDashboardData()
        
      } catch (error) {
        console.error('Error bulk shipping:', error)
        alert('Error shipping packing lists: ' + (error.response?.data?.message || error.message))
      } finally {
        bulkShipping.value = false
      }
    }

    const handlePendingAction = (item) => {
      switch (item.id) {
        case 1:
          // Navigate to overdue packing lists
          window.location.href = '/packing-lists?status=In Progress'
          break
        case 2:
          // Navigate to inventory
          window.location.href = '/inventory'
          break
        case 3:
          // Navigate to available deliveries
          window.location.href = '/deliveries/available-for-packing'
          break
      }
    }

    // Utility methods
    const getStatusClass = (status) => {
      const classes = {
        'Draft': 'status-draft',
        'In Progress': 'status-progress',
        'Completed': 'status-completed',
        'Shipped': 'status-shipped'
      }
      return classes[status] || 'status-draft'
    }

    const getActivityIconClass = (type) => {
      const classes = {
        'draft': 'activity-draft',
        'in_progress': 'activity-progress',
        'completed': 'activity-completed',
        'shipped': 'activity-shipped'
      }
      return classes[type] || 'activity-draft'
    }

    const getActivityIcon = (type) => {
      const icons = {
        'draft': 'fa-edit',
        'in_progress': 'fa-tasks',
        'completed': 'fa-check',
        'shipped': 'fa-shipping-fast'
      }
      return icons[type] || 'fa-edit'
    }

    const formatWeight = (weight) => {
      return weight ? `${weight.toFixed(2)} kg` : '0 kg'
    }

    const formatVolume = (volume) => {
      return volume ? `${volume.toFixed(3)} m³` : '0 m³'
    }

    const formatRelativeTime = (date) => {
      if (!date) return 'Unknown'
      const now = new Date()
      const past = new Date(date)
      const diffMs = now - past
      const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
      const diffDays = Math.floor(diffHours / 24)
      
      if (diffDays > 0) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`
      if (diffHours > 0) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`
      return 'Just now'
    }

    return {
      loading,
      refreshing,
      showBulkModal,
      bulkShipping,
      selectedForShipping,
      stats,
      recentActivity,
      pendingItems,
      completedPackingLists,
      statusDistribution,
      refreshData,
      openBulkShipping,
      bulkShip,
      handlePendingAction,
      getStatusClass,
      getActivityIconClass,
      getActivityIcon,
      formatWeight,
      formatVolume,
      formatRelativeTime
    }
  }
}
</script>

<style scoped>
.packing-dashboard {
  padding: 24px;
  background: #f8fafc;
  min-height: 100vh;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.dashboard-title {
  margin: 0;
  font-size: 32px;
  font-weight: 700;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 12px;
}

.dashboard-title i {
  color: #3b82f6;
}

.dashboard-subtitle {
  margin: 8px 0 0 0;
  color: #6b7280;
  font-size: 16px;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.stats-section {
  margin-bottom: 32px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 24px;
}

.stat-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 20px;
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
}

.stat-card.primary::before { background: #3b82f6; }
.stat-card.warning::before { background: #f59e0b; }
.stat-card.success::before { background: #10b981; }
.stat-card.info::before { background: #06b6d4; }

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.stat-card.primary .stat-icon { background: #3b82f6; }
.stat-card.warning .stat-icon { background: #f59e0b; }
.stat-card.success .stat-icon { background: #10b981; }
.stat-card.info .stat-icon { background: #06b6d4; }

.stat-number {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 14px;
  color: #6b7280;
  font-weight: 600;
  margin-bottom: 8px;
}

.stat-subtext {
  font-size: 12px;
  color: #9ca3af;
}

.stat-change {
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.stat-change.positive {
  color: #059669;
}

.metrics-section {
  margin-bottom: 32px;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
}

.metric-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.metric-title {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 8px;
}

.metric-value {
  font-size: 24px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 16px;
}

.metric-progress {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.progress-bar {
  height: 8px;
  background: #f3f4f6;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #3b82f6;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 12px;
  color: #6b7280;
}

.overview-section {
  margin-bottom: 32px;
}

.overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 24px;
}

.overview-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-title {
  margin: 0 0 20px 0;
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-chart {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.status-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.status-bar {
  display: flex;
  align-items: center;
  gap: 12px;
}

.status-label {
  min-width: 100px;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.progress-container {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.status-count {
  min-width: 30px;
  text-align: right;
  font-weight: 600;
  color: #1f2937;
}

.progress-fill.status-draft { background: #6b7280; }
.progress-fill.status-progress { background: #f59e0b; }
.progress-fill.status-completed { background: #10b981; }
.progress-fill.status-shipped { background: #3b82f6; }

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  max-height: 300px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.activity-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 12px;
}

.activity-icon.activity-draft { background: #6b7280; }
.activity-icon.activity-progress { background: #f59e0b; }
.activity-icon.activity-completed { background: #10b981; }
.activity-icon.activity-shipped { background: #3b82f6; }

.activity-content {
  flex: 1;
}

.activity-title {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 2px;
}

.activity-description {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 4px;
}

.activity-time {
  font-size: 11px;
  color: #9ca3af;
}

.actions-section {
  margin-bottom: 32px;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 24px;
}

.action-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.action-btn {
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 12px;
  text-align: left;
}

.action-btn:hover {
  border-color: #3b82f6;
  background: #f8fafc;
}

.action-btn i {
  color: #3b82f6;
  font-size: 16px;
}

.action-btn span {
  font-weight: 600;
  color: #374151;
}

.pending-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.pending-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
  border-left: 4px solid transparent;
}

.pending-item .pending-icon.high {
  background: #ef4444;
  border-left-color: #ef4444;
}

.pending-item .pending-icon.medium {
  background: #f59e0b;
  border-left-color: #f59e0b;
}

.pending-item .pending-icon.low {
  background: #06b6d4;
  border-left-color: #06b6d4;
}

.pending-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 12px;
}

.pending-content {
  flex: 1;
}

.pending-title {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 2px;
}

.pending-description {
  font-size: 12px;
  color: #6b7280;
}

.pending-action {
  padding: 8px 16px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
  transition: background-color 0.2s;
}

.pending-action:hover {
  background: #2563eb;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-outline {
  background: white;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-outline:hover {
  background: #f9fafb;
}

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background: #4b5563;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 24px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.btn-close {
  width: 32px;
  height: 32px;
  border: none;
  background: #f3f4f6;
  border-radius: 8px;
  cursor: pointer;
  font-size: 18px;
}

.modal-body {
  padding: 24px;
}

.bulk-list {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
}

.bulk-item {
  padding: 12px 0;
  border-bottom: 1px solid #f3f4f6;
}

.bulk-item:last-child {
  border-bottom: none;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  margin: 0;
}

.modal-footer {
  padding: 24px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.loading-content {
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .dashboard-header {
    flex-direction: column;
    gap: 16px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .metrics-grid {
    grid-template-columns: 1fr;
  }
  
  .overview-grid {
    grid-template-columns: 1fr;
  }
  
  .actions-grid {
    grid-template-columns: 1fr;
  }
}
</style>