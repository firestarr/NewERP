<!-- src/views/Dashboard.vue -->
<template>
  <div class="dashboard">
    <div class="welcome-container">
        <!-- Welcome Text -->
        <h1 class="welcome-text">WELCOME TO AOS</h1>
      <!-- Logo Container -->
      <div class="logo-container">
        <!-- Logo Image (jika ada file gambar) -->
        <img
          :src="logoArm"
          alt="Armwest Miraitech India Pvt Ltd"
          class="company-logo"
          @error="showFallbackLogo = true"
          v-if="!showFallbackLogo"
        />

        <!-- Logo CSS (fallback jika gambar tidak ada) -->
        <!-- <div class="logo-css" v-if="showFallbackLogo">
          <div class="logo-symbol">
            <div class="letter-a">A</div>
            <div class="letter-m">M</div>
          </div>
        </div> -->

        <!-- Company Name Text -->
        <div class="company-name">
          <div class="company-main">ARMWEST MIRAITECH INDIA</div>
          <div class="company-sub">PRIVATE LIMITED</div>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import logoArm from '@/assets/ARMWEST.png';

export default {
  name: 'DashboardView',
  setup() {
    const showFallbackLogo = ref(false);

    return {
      showFallbackLogo,
      logoArm
    };
  }
};
</script>

<style scoped>
.dashboard {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 2rem;
  padding-top: 3rem;
}

.welcome-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3rem;
  text-align: center;
}

/* Logo Container */
.logo-container {
  display: flex;
  align-items: center;
  gap: 2rem;
  padding: 2rem;
  background: white;
  border-radius: 1rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Logo Image */
.company-logo {
  height: 80px;
  width: auto;
  object-fit: contain;
  max-width: 120px;
}

/* Logo CSS (fallback) */
.logo-css {
  display: flex;
  align-items: center;
  margin-right: 1rem;
}

.logo-symbol {
  position: relative;
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.letter-a,
.letter-m {
  position: absolute;
  font-family: 'Arial Black', sans-serif;
  font-weight: 900;
  font-size: 48px;
  color: #dc2626;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

.letter-a {
  transform: translateX(-12px) translateY(-3px);
  z-index: 1;
}

.letter-m {
  transform: translateX(12px) translateY(3px);
  z-index: 2;
}

.company-name {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.company-main {
  font-family: 'Arial', sans-serif;
  font-weight: 700;
  font-size: 28px;
  color: #2c3e50;
  letter-spacing: 1px;
  margin-bottom: 0.5rem;
}

.company-sub {
  font-family: 'Arial', sans-serif;
  font-weight: 600;
  font-size: 20px;
  color: #7f8c8d;
  letter-spacing: 0.5px;
}

/* Welcome Text */
.welcome-text {
  font-family: 'Arial', sans-serif;
  font-size: 3rem;
  font-weight: 700;
  color: #2c3e50;
  margin: 0;
  letter-spacing: 2px;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
}

/* Responsive Design */
@media (max-width: 1024px) {
  .logo-container {
    flex-direction: column;
    gap: 1rem;
    padding: 1.5rem;
  }

  .logo-css {
    margin-right: 0;
    margin-bottom: 0.5rem;
  }

  .company-main {
    font-size: 24px;
  }

  .company-sub {
    font-size: 18px;
  }

  .welcome-text {
    font-size: 2.5rem;
  }
}

@media (max-width: 768px) {
  .welcome-container {
    gap: 2rem;
  }

  .logo-container {
    text-align: center;
  }

  .company-logo {
    height: 70px;
    max-width: 110px;
  }

  .logo-symbol {
    width: 60px;
    height: 60px;
  }

  .letter-a,
  .letter-m {
    font-size: 36px;
  }

  .letter-a {
    transform: translateX(-9px) translateY(-2px);
  }

  .letter-m {
    transform: translateX(9px) translateY(2px);
  }

  .company-main {
    font-size: 20px;
  }

  .company-sub {
    font-size: 16px;
  }

  .welcome-text {
    font-size: 2rem;
    letter-spacing: 1px;
  }
}

@media (max-width: 480px) {
  .dashboard {
    padding: 1rem;
  }

  .logo-container {
    padding: 1rem;
  }

  .company-logo {
    height: 60px;
    max-width: 100px;
  }

  .logo-symbol {
    width: 50px;
    height: 50px;
  }

  .letter-a,
  .letter-m {
    font-size: 28px;
  }

  .letter-a {
    transform: translateX(-7px) translateY(-2px);
  }

  .letter-m {
    transform: translateX(7px) translateY(2px);
  }

  .company-main {
    font-size: 16px;
  }

  .company-sub {
    font-size: 14px;
  }

  .welcome-text {
    font-size: 1.5rem;
    letter-spacing: 0.5px;
  }
}
</style>
