<template>
  <div class="falling-pizzas-container"></div>
</template>

<script setup>
import { onMounted, onBeforeUnmount } from 'vue';

// Config
const pizzaCount = 25;
const speedRange = [8, 15];
const sizeRange = [30, 80];

const pizzaImages = [
  'https://cdn-icons-png.flaticon.com/512/6978/6978255.png',
  'https://cdn-icons-png.flaticon.com/512/3132/3132693.png',
  'https://cdn-icons-png.flaticon.com/512/1404/1404945.png',
  'https://cdn-icons-png.flaticon.com/512/706/706934.png',
  'https://cdn-icons-png.flaticon.com/512/2094/2094661.png',
  'https://cdn-icons-png.flaticon.com/512/6635/6635089.png'
];

// Function to generate a random value in the range
function random(min, max) {
  return Math.random() * (max - min) + min;
}

function createPizza(container) {
  // Creating a pizza element
  const pizza = document.createElement('img');
  pizza.className = 'falling-pizza';

  // Random selection of pizza image
  pizza.src = pizzaImages[Math.floor(Math.random() * pizzaImages.length)];

  // Random animation parameters
  const size = random(sizeRange[0], sizeRange[1]);
  const xPos = random(5, 95);
  const speed = random(speedRange[0], speedRange[1]);
  const initialY = random(-200, -50);
  const randomRotation = Math.floor(random(0, 360));

  // Style pizza
  pizza.style.width = `${size}px`;
  pizza.style.left = `${xPos}%`;
  pizza.style.top = `${initialY}px`;
  pizza.style.transform = `rotate(${randomRotation}deg)`;
  pizza.style.animationDuration = `${speed}s`;
  pizza.style.animationDelay = `${random(0, 5)}s`;

  container.appendChild(pizza);

  pizza.addEventListener('animationend', function() {
    this.remove();
    createPizza(container);
  });
}

// Create style sheet
function createStyleSheet() {
  const styleSheet = document.createElement('style');
  styleSheet.id = 'pizza-animation-styles';
  styleSheet.textContent = `
    .falling-pizzas-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      overflow: hidden;
      pointer-events: none;
    }

    .falling-pizza {
      position: absolute;
      opacity: 0.7;
      animation: fall linear forwards;
      filter: drop-shadow(0px 3px 5px rgba(0, 0, 0, 0.2));
      z-index: -1;
    }

    @keyframes fall {
      0% {
        transform-origin: center;
        opacity: 0;
      }
      10% {
        opacity: 0.7;
      }
      90% {
        opacity: 0.7;
      }
      100% {
        top: calc(100vh + 100px);
        opacity: 0;
      }
    }
  `;
  return styleSheet;
}

onMounted(() => {
  const styleSheet = createStyleSheet();
  document.head.appendChild(styleSheet);

  const pizzaContainer = document.querySelector('.falling-pizzas-container');

  // Creating falling pizzas
  for (let i = 0; i < pizzaCount; i++) {
    createPizza(pizzaContainer);
  }
});

onBeforeUnmount(() => {
  // Clean up style element on component unmount
  const styleElement = document.getElementById('pizza-animation-styles');
  if (styleElement) {
    styleElement.remove();
  }
});
</script>