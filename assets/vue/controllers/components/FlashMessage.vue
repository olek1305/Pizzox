<template>
  <div
      :class="['fixed top-4 left-1/2 transform -translate-x-1/2 bg-opacity-90 p-4 rounded shadow-lg text-white z-50', bgColor]"
      role="alert"
  >
    {{ message }}
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  message: String,
  type: {
    type: String,
    default: 'success'
  }
});

const bgColor = computed(() => {
  switch (props.type) {
    case 'success':
      return 'bg-green-500';
    case 'error':
      return 'bg-red-500';
    case 'warning':
      return 'bg-yellow-500 text-black';
    default:
      return 'bg-blue-500';
  }
});
</script>

<style scoped>
.flash-message-enter-active, .flash-message-leave-active {
  transition: opacity 0.5s, transform 0.5s;
}
.flash-message-enter-from, .flash-message-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
.flash-message-enter-to, .flash-message-leave-from {
  opacity: 1;
  transform: translateY(0);
}
</style>
