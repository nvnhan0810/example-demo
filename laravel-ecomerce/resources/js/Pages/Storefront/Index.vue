<template>
  <div class="storefront-container">
    <!-- Header hiển thị giỏ hàng và Nút thêm mới -->
    <header class="header">
      <h1>Cửa Hàng</h1>
      <div class="header-actions">
        <div class="cart-info">
          <span>🛒 Giỏ hàng: <strong>{{ cartCount }}</strong> sản phẩm</span>
        </div>
        <!-- Nút Tạo sản phẩm mới -->
        <Link href="/products/create" class="create-btn">
          + Thêm sản phẩm
        </Link>
      </div>
    </header>

    <!-- Danh sách sản phẩm -->
    <main class="product-grid">
      <div 
        v-for="product in products" 
        :key="product.id" 
        class="product-card"
      >
        <h2 class="product-name">{{ product.name }}</h2>
        <p class="product-price">{{ formatPrice(product.price) }}</p>
        
        <!-- Các nút hành động của sản phẩm -->
        <div class="action-buttons">
          <button 
            @click="addToCart(product.id)" 
            :disabled="isAdding === product.id"
            class="add-button"
          >
            {{ isAdding === product.id ? 'Đang thêm...' : 'Thêm vào giỏ' }}
          </button>

          <!-- Nút Chỉnh sửa sản phẩm -->
          <Link :href="`/products/${product.id}/edit`" class="edit-btn">
            Sửa
          </Link>
        </div>
      </div>
    </main>

    <!-- Thông báo nếu không có sản phẩm -->
    <div v-if="products.length === 0" class="empty-state">
      Hiện chưa có sản phẩm nào.
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
// Import thêm component Link từ Inertia
import { router, Link } from '@inertiajs/vue3'

interface Product {
  id: number;
  name: string;
  price: number;
}

defineProps<{
  products: Product[];
  cartCount: number;
}>();

const isAdding = ref<number | null>(null);

const addToCart = (productId: number) => {
  isAdding.value = productId;
  router.post('/add-to-cart', { product_id: productId }, {
    preserveScroll: true, 
    onFinish: () => {
      isAdding.value = null;
    }
  });
};

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('vi-VN', { 
    style: 'currency', 
    currency: 'VND' 
  }).format(price);
};
</script>

<style scoped>
.storefront-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  font-family: sans-serif;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 20px;
}

.create-btn {
  background-color: #3182ce;
  color: white;
  text-decoration: none;
  padding: 8px 16px;
  border-radius: 4px;
  font-weight: bold;
  transition: background-color 0.2s;
}

.create-btn:hover {
  background-color: #2b6cb0;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
}

.product-card {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 20px;
  text-align: center;
  transition: transform 0.2s;
  display: flex;
  flex-direction: column;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.product-name {
  margin-top: 0;
  font-size: 1.25rem;
}

.product-price {
  color: #e53e3e;
  font-weight: bold;
  font-size: 1.2rem;
  margin: 10px 0 20px;
}

/* Flexbox để dàn 2 nút bấm ngang nhau */
.action-buttons {
  display: flex;
  gap: 10px;
  margin-top: auto; /* Đẩy các nút xuống đáy thẻ card */
}

.add-button {
  flex: 2; /* Chiếm nhiều không gian hơn */
  background-color: #42b883;
  color: white;
  border: none;
  padding: 10px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
}

.add-button:disabled {
  background-color: #a0aec0;
  cursor: not-allowed;
}

.edit-btn {
  flex: 1; /* Chiếm ít không gian hơn */
  background-color: transparent;
  color: #4a5568;
  border: 1px solid #cbd5e0;
  padding: 10px;
  border-radius: 4px;
  text-decoration: none;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.edit-btn:hover {
  background-color: #edf2f7;
  color: #2d3748;
}

.empty-state {
  text-align: center;
  color: #718096;
  margin-top: 50px;
}
</style>