// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
  // Initialize all functionality
  initializeTabs();
  initializeAccountSettings();
  initializeSchedulerButtons();
  initializeModals();
  initializeDragAndDrop();
  initializeViewSchedules();
});

// Tab functionality
function initializeTabs() {
  const todayTab = document.getElementById('today-requests-tab');
  const futureTab = document.getElementById('future-requests-tab');
  const todayContent = document.getElementById('today-requests-content');
  const futureContent = document.getElementById('future-requests-content');

  if (todayTab && futureTab && todayContent && futureContent) {
    todayTab.addEventListener('click', () => {
      todayTab.classList.add('bg-primary-light', 'dark:bg-primary-dark', 'text-white');
      todayTab.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-900', 'dark:text-white');
      futureTab.classList.remove('bg-primary-light', 'dark:bg-primary-dark', 'text-white');
      futureTab.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-900', 'dark:text-white');
      todayContent.classList.remove('hidden');
      futureContent.classList.add('hidden');
    });

    futureTab.addEventListener('click', () => {
      futureTab.classList.add('bg-primary-light', 'dark:bg-primary-dark', 'text-white');
      futureTab.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-900', 'dark:text-white');
      todayTab.classList.remove('bg-primary-light', 'dark:bg-primary-dark', 'text-white');
      todayTab.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-900', 'dark:text-white');
      futureContent.classList.remove('hidden');
      todayContent.classList.add('hidden');
    });
  }
}

// Account settings functionality
function initializeAccountSettings() {
  const accountSettingsBtn = document.getElementById('account-settings');
  const closeAccountSettingsBtn = document.getElementById('close-account-settings');
  const accountSettingsModal = document.getElementById('account-settings-modal');
  const changeUsernameCheckbox = document.getElementById('change-username');
  const changePasswordCheckbox = document.getElementById('change-password');
  const accountSettingsForm = document.getElementById('account-settings-form');

  if (accountSettingsBtn && accountSettingsModal) {
    accountSettingsBtn.addEventListener('click', () => {
      accountSettingsModal.style.display = 'flex';
    });
  }

  if (closeAccountSettingsBtn && accountSettingsModal) {
    closeAccountSettingsBtn.addEventListener('click', () => {
      accountSettingsModal.style.display = 'none';
    });
  }

  if (changeUsernameCheckbox) {
    changeUsernameCheckbox.addEventListener('change', function() {
      const usernameInput = document.getElementById('new-username');
      if (usernameInput) {
        usernameInput.disabled = !this.checked;
        usernameInput.required = this.checked;
      }
    });
  }

  if (changePasswordCheckbox) {
    changePasswordCheckbox.addEventListener('change', function() {
      const passwordInput = document.getElementById('new-password');
      if (passwordInput) {
        passwordInput.disabled = !this.checked;
        passwordInput.required = this.checked;
      }
    });
  }

  if (accountSettingsForm) {
    accountSettingsForm.addEventListener('submit', handleAccountSettingsSubmit);
  }
}

// Scheduler buttons functionality
function initializeSchedulerButtons() {
  const addSchedulerBtn = document.getElementById('add-scheduler-btn');
  if (addSchedulerBtn) {
    addSchedulerBtn.addEventListener('click', handleAddScheduler);
  }

  // Initialize delete buttons
  document.querySelectorAll('.delete-scheduler-btn').forEach(button => {
    button.addEventListener('click', function() {
      const schedulerName = this.getAttribute('data-scheduler');
      const deleteModal = document.getElementById('delete-scheduler-modal');
      if (deleteModal) {
        deleteModal.style.display = 'block';
      }
    });
  });

  // Initialize rename buttons
  document.querySelectorAll('.rename-scheduler-btn').forEach(button => {
    button.addEventListener('click', function() {
      const schedulerName = this.getAttribute('data-scheduler');
      const newNameInput = document.getElementById('new-scheduler-name');
      const renameModal = document.getElementById('rename-scheduler-modal');
      if (newNameInput && renameModal) {
        newNameInput.value = schedulerName;
        renameModal.style.display = 'block';
      }
    });
  });
}

// Modal functionality
function initializeModals() {
  // Delete scheduler modal
  const cancelDeleteBtn = document.getElementById('cancel-delete-scheduler');
  const confirmDeleteBtn = document.getElementById('confirm-delete-scheduler');
  const deleteModal = document.getElementById('delete-scheduler-modal');

  if (cancelDeleteBtn && deleteModal) {
    cancelDeleteBtn.addEventListener('click', () => {
      deleteModal.style.display = 'none';
    });
  }

  if (confirmDeleteBtn && deleteModal) {
    confirmDeleteBtn.addEventListener('click', handleDeleteScheduler);
  }

  // Rename scheduler modal
  const renameForm = document.getElementById('rename-scheduler-form');
  const closeRenameBtn = document.getElementById('close-rename-scheduler');
  const renameModal = document.getElementById('rename-scheduler-modal');

  if (renameForm) {
    renameForm.addEventListener('submit', handleRenameScheduler);
  }

  if (closeRenameBtn && renameModal) {
    closeRenameBtn.addEventListener('click', () => {
      renameModal.style.display = 'none';
      const newNameInput = document.getElementById('new-scheduler-name');
      if (newNameInput) {
        newNameInput.value = '';
      }
    });
  }

  // Schedule modal
  const scheduleForm = document.getElementById('schedule-form');
  const cancelModal = document.getElementById('cancel-modal');
  const scheduleModal = document.getElementById('schedule-modal');

  if (scheduleForm) {
    scheduleForm.addEventListener('submit', handleScheduleSubmit);
  }

  if (cancelModal && scheduleModal) {
    cancelModal.addEventListener('click', () => {
      scheduleModal.style.display = 'none';
    });
  }
}

// Drag and drop functionality
function initializeDragAndDrop() {
  const schedulers = document.querySelectorAll(".circle");
  const rooms = document.querySelectorAll(".room");
  const scheduleModal = document.getElementById("schedule-modal");
  let draggedScheduler = null;

  schedulers.forEach((scheduler) => {
    scheduler.addEventListener("dragstart", (e) => {
      draggedScheduler = e.target;
    });

    scheduler.addEventListener("dragend", () => {
      draggedScheduler = null;
    });
  });

  rooms.forEach((room) => {
    room.addEventListener("dragover", (e) => e.preventDefault());
    room.addEventListener("drop", (e) => {
      e.preventDefault();
      if (draggedScheduler && scheduleModal) {
        const schedulerName = draggedScheduler.textContent.trim();
        const roomNumber = room.getAttribute("data-room");
        const schedulerNameSpan = document.getElementById("modal-scheduler-name");
        const roomNumberSpan = document.getElementById("modal-room-number");
        
        if (schedulerNameSpan && roomNumberSpan) {
          schedulerNameSpan.textContent = schedulerName;
          roomNumberSpan.textContent = roomNumber;
          scheduleModal.style.display = "block";
        }
      }
    });
  });
}

// View schedules functionality
function initializeViewSchedules() {
  document.querySelectorAll('.view-schedules-btn').forEach(button => {
    button.addEventListener('click', function() {
      const roomId = this.getAttribute('data-room');
      const scheduleList = document.getElementById('schedule-list');
      const roomNumber = document.getElementById('room-number');
      const viewSchedulesModal = document.getElementById('view-schedules-modal');

      if (roomNumber && scheduleList && viewSchedulesModal) {
        roomNumber.textContent = roomId;
        scheduleList.innerHTML = '';

        fetch(`getRoomSchedules.php?room=${roomId}`)
          .then(response => response.json())
          .then(data => {
            if (data.length > 0) {
              data.forEach(schedule => {
                const scheduleItem = document.createElement('li');
                scheduleItem.innerHTML = `
                  <strong>Scheduler Name:</strong> ${schedule.scheduler_name}<br>
                  <strong>Date:</strong> ${schedule.schedule_date}<br>
                  <strong>Time:</strong> ${schedule.start_time} - ${schedule.end_time}<br>
                `;
                scheduleList.appendChild(scheduleItem);
              });
            } else {
              scheduleList.innerHTML = '<li>No schedules found for this room.</li>';
            }
          })
          .catch(error => {
            console.error('Error fetching room schedules:', error);
            scheduleList.innerHTML = '<li>Error fetching schedules.</li>';
          });

        viewSchedulesModal.style.display = 'block';
      }
    });
  });

  // Close modal functionality
  const closeModal = document.getElementById('close-modal');
  const viewSchedulesModal = document.getElementById('view-schedules-modal');

  if (closeModal && viewSchedulesModal) {
    closeModal.addEventListener('click', () => {
      viewSchedulesModal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
      if (event.target === viewSchedulesModal) {
        viewSchedulesModal.style.display = 'none';
      }
    });
  }
}

// Event handlers
function handleAccountSettingsSubmit(event) {
  event.preventDefault();
  const newUsername = document.getElementById('new-username')?.value;
  const newPassword = document.getElementById('new-password')?.value;
  const changeUsernameCheckbox = document.getElementById('change-username');

  if (changeUsernameCheckbox?.checked && !newUsername) {
    alert('Please provide a new username!');
    return;
  }

  if (changeUsernameCheckbox?.checked) {
    fetch('../../php/check-username.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username: newUsername })
    })
    .then(response => response.json())
    .then(data => {
      const usernameError = document.getElementById('username-error');
      if (!data.available) {
        if (usernameError) usernameError.style.display = 'inline';
      } else {
        if (usernameError) usernameError.style.display = 'none';
        updateAccount(newUsername, newPassword);
      }
    })
    .catch(error => console.log('Error:', error));
  } else {
    updateAccount(newUsername, newPassword);
  }
}

function handleAddScheduler() {
  const scheduleName = prompt("Enter Scheduler Name:");
  if (scheduleName) {
    fetch('teacher.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        'add_scheduler': true,
        'schedule_name': scheduleName
      })
    })
    .then(response => response.text())
    .then(data => {
      if (data === "Scheduler added successfully!") {
        alert("Scheduler added successfully!");
        window.location.reload();
      } else {
        alert(data);
      }
    })
    .catch(error => {
      console.error("Error adding scheduler:", error);
      alert("There was an error adding the scheduler.");
    });
  }
}

function handleDeleteScheduler() {
  const selectedScheduler = document.querySelector('.delete-scheduler-btn[data-scheduler]')?.getAttribute('data-scheduler');
  if (selectedScheduler) {
    fetch('delete-scheduler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        'scheduler_name': selectedScheduler
      })
    })
    .then(response => response.text())
    .then(result => {
      if (result.includes('successfully')) {
        alert('Scheduler deleted successfully');
        window.location.reload();
      } else {
        alert('Failed to delete scheduler');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while deleting the scheduler');
    });
  }
}

function handleRenameScheduler(event) {
  event.preventDefault();
  const oldSchedulerName = document.querySelector('.rename-scheduler-btn[data-scheduler]')?.getAttribute('data-scheduler');
  const newSchedulerName = document.getElementById('new-scheduler-name')?.value;

  if (oldSchedulerName && newSchedulerName) {
    fetch('rename-scheduler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        'old_scheduler_name': oldSchedulerName,
        'new_scheduler_name': newSchedulerName
      })
    })
    .then(response => response.text())
    .then(result => {
      alert(result);
      const renameModal = document.getElementById('rename-scheduler-modal');
      if (renameModal) {
        renameModal.style.display = 'none';
      }
      window.location.reload();
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while renaming the scheduler');
    });
  }
}

function handleScheduleSubmit(event) {
  event.preventDefault();
  const schedulerName = document.getElementById("modal-scheduler-name")?.textContent;
  const roomNumber = document.getElementById("modal-room-number")?.textContent;
  const scheduleDate = document.getElementById("schedule-date")?.value;
  const startTime = document.getElementById("start-time")?.value;
  const durationHours = document.getElementById("duration-hours")?.value;

  if (schedulerName && roomNumber && scheduleDate && startTime && durationHours) {
    fetch("../../php/schedule-handler.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        scheduler_name: schedulerName,
        room_number: roomNumber,
        schedule_date: scheduleDate,
        start_time: startTime,
        duration_hours: durationHours,
      }),
    })
    .then(response => response.text())
    .then(data => {
      alert(data);
      if (data.includes("success")) {
        const scheduleModal = document.getElementById("schedule-modal");
        if (scheduleModal) {
          scheduleModal.style.display = "none";
        }
        window.location.reload();
      }
    })
    .catch(error => {
      console.error("Error:", error);
      alert("An error occurred while scheduling");
    });
  }
}

// Update account function
function updateAccount(newUsername, newPassword) {
  const updatedUsername = document.getElementById('change-username')?.checked ? newUsername : null;
  const updatedPassword = document.getElementById('change-password')?.checked ? newPassword : null;

  fetch('../../php/update-account.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      username: updatedUsername,
      password: updatedPassword
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Account updated successfully!');
      const accountSettingsModal = document.getElementById('account-settings-modal');
      if (accountSettingsModal) {
        accountSettingsModal.style.display = 'none';
      }
      if (updatedUsername) {
        const usernameSpan = document.querySelector('#navbar .nav-left span');
        if (usernameSpan) {
          usernameSpan.textContent = `Hello, ${updatedUsername}`;
        }
      }
      window.location.reload();
    } else {
      alert('Error updating account!');
    }
  })
  .catch(error => console.log('Error:', error));
}