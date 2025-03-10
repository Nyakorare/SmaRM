// Tab functionality
const activeTab = document.getElementById('active-tab');
const futureTab = document.getElementById('future-tab');
const activeContent = document.getElementById('active-schedules');
const futureContent = document.getElementById('future-schedules');

// Add event listeners to tabs
activeTab.addEventListener('click', () => {
  activeTab.classList.add('active');
  futureTab.classList.remove('active');
  activeContent.classList.add('active');
  futureContent.classList.remove('active');
});

futureTab.addEventListener('click', () => {
  futureTab.classList.add('active');
  activeTab.classList.remove('active');
  futureContent.classList.add('active');
  activeContent.classList.remove('active');
});