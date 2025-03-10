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

// Show the account settings modal
document.getElementById('account-settings').addEventListener('click', function() {
  document.getElementById('account-settings-modal').style.display = 'flex';
});

// Close the modal
document.getElementById('close-account-settings').addEventListener('click', function() {
  document.getElementById('account-settings-modal').style.display = 'none';
});

// Enable/disable username input based on checkbox
document.getElementById('change-username').addEventListener('change', function() {
  const usernameInput = document.getElementById('new-username');
  usernameInput.disabled = !this.checked;
  if (this.checked) {
    usernameInput.required = true; // Make username field required when checkbox is checked
  } else {
    usernameInput.required = false; // Remove required if checkbox is unchecked
  }
});

// Enable/disable password input based on checkbox
document.getElementById('change-password').addEventListener('change', function() {
  const passwordInput = document.getElementById('new-password');
  passwordInput.disabled = !this.checked;
  if (this.checked) {
    passwordInput.required = true; // Make password field required when checkbox is checked
  } else {
    passwordInput.required = false; // Remove required if checkbox is unchecked
  }
});

// Handle the form submission to update the username and password
document.getElementById('account-settings-form').addEventListener('submit', function(event) {
  event.preventDefault();

  const newUsername = document.getElementById('new-username').value;
  const newPassword = document.getElementById('new-password').value;

  // Only check username if it's being changed
  let usernameValid = true;
  if (document.getElementById('change-username').checked) {
    if (!newUsername) {
      alert('Please provide a new username!');
      return;
    }
    
    // Check if the username already exists
    fetch('../../php/check-username.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ username: newUsername })
    })
    .then(response => response.json())
    .then(data => {
      if (!data.available) {
        document.getElementById('username-error').style.display = 'inline';
        usernameValid = false;
      } else {
        document.getElementById('username-error').style.display = 'none';
        updateAccount(newUsername, newPassword);
      }
    })
    .catch(error => console.log('Error:', error));
  } else {
    updateAccount(newUsername, newPassword); // No need to check username if not changing it
  }
});

// Function to handle account update
function updateAccount(newUsername, newPassword) {
  const updatedUsername = document.getElementById('change-username').checked ? newUsername : null;
  const updatedPassword = document.getElementById('change-password').checked ? newPassword : null;

  fetch('../../php/update-account.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      username: updatedUsername,
      password: updatedPassword
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Account updated successfully!');
      // Close the modal
      document.getElementById('account-settings-modal').style.display = 'none';
      // Optionally update the username in the navbar without reloading
      if (updatedUsername) {
        document.querySelector('#navbar .nav-left span').textContent = `Hello, ${updatedUsername}`;
      }
    } else {
      alert('Error updating account!');
    }
  })
  .catch(error => console.log('Error:', error));
}

document.getElementById('add-scheduler-btn').addEventListener('click', function() {
    const scheduleName = prompt("Enter Scheduler Name:");

    if (scheduleName) {
        // Send AJAX request to add scheduler
        fetch('teacher.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'add_scheduler': true,
                'schedule_name': scheduleName
            })
        })
        .then(response => response.text())
        .then(data => {
            if (data === "Scheduler added successfully!") {
                alert("Scheduler added successfully!");
                location.reload();  // Reload the page after successful add
            } else {
                alert(data);  // Display the error message (e.g., "Scheduler name already exists.")
            }
        })
        .catch(error => {
            console.error("Error adding scheduler:", error);
            alert("There was an error adding the scheduler.");
        });
    }
    window.location.reload();
});

document.addEventListener('DOMContentLoaded', function () {
  // Handle the delete button click
  const deleteButtons = document.querySelectorAll('.delete-scheduler-btn');
  let selectedScheduler = '';  // Store the scheduler name to delete
  
  deleteButtons.forEach(button => {
    button.addEventListener('click', function () {
      selectedScheduler = this.getAttribute('data-scheduler');
      document.getElementById('delete-scheduler-modal').style.display = 'block';
    });
  });

  // Handle the cancel button click
  document.getElementById('cancel-delete-scheduler').addEventListener('click', function () {
    document.getElementById('delete-scheduler-modal').style.display = 'none';
  });

  // Handle the confirm delete button click
  document.getElementById('confirm-delete-scheduler').addEventListener('click', function () {
    if (selectedScheduler) {
      // Make an AJAX request to delete the scheduler
      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'delete-scheduler.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () {
        if (xhr.status === 200) {
          alert('Scheduler deleted successfully');
          // Reload the page or remove the scheduler from the UI
          location.reload();
        } else {
          alert('Failed to delete scheduler');
        }
      };
      xhr.send('scheduler_name=' + encodeURIComponent(selectedScheduler));
    }
    document.getElementById('delete-scheduler-modal').style.display = 'none';
  });
});

// Event listener for renaming scheduler
document.querySelectorAll('.rename-scheduler-btn').forEach(button => {
  button.addEventListener('click', function () {
      const schedulerName = this.getAttribute('data-scheduler');
      document.getElementById('new-scheduler-name').value = schedulerName; // Pre-fill the scheduler name in the modal
      document.getElementById('rename-scheduler-modal').style.display = 'block'; // Show the rename modal
  });
});

// Handle the renaming form submission
document.getElementById('rename-scheduler-form').addEventListener('submit', function (e) {
  e.preventDefault();

  const oldSchedulerName = document.querySelector('.rename-scheduler-btn[data-scheduler]').getAttribute('data-scheduler');
  const newSchedulerName = document.getElementById('new-scheduler-name').value;

  // Send an AJAX request to the server to rename the scheduler
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'rename-scheduler.php', true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onload = function () {
      if (xhr.status === 200) {
          alert(xhr.responseText);
          document.getElementById('rename-scheduler-modal').style.display = 'none'; // Close the modal
          location.reload(); // Reload the page to show the updated scheduler name
      }
  };
  xhr.send('old_scheduler_name=' + encodeURIComponent(oldSchedulerName) + '&new_scheduler_name=' + encodeURIComponent(newSchedulerName));
});

// Handle cancel action for rename modal
document.getElementById('close-rename-scheduler').addEventListener('click', function () {
  // Hide the rename modal
  document.getElementById('rename-scheduler-modal').style.display = 'none';
  // Clear the input field to reset it for the next open
  document.getElementById('new-scheduler-name').value = '';
});

document.addEventListener("DOMContentLoaded", () => {
  // Drag-and-drop logic
  const schedulers = document.querySelectorAll(".circle");
  const rooms = document.querySelectorAll(".room");
  const scheduleModal = document.getElementById("schedule-modal");

  let draggedScheduler = null;

  // Drag events for schedulers
  schedulers.forEach((scheduler) => {
      scheduler.addEventListener("dragstart", (e) => {
          draggedScheduler = e.target;
      });

      scheduler.addEventListener("dragend", () => {
          draggedScheduler = null;
      });
  });

  // Drag events for rooms
  rooms.forEach((room) => {
      room.addEventListener("dragover", (e) => e.preventDefault());
      room.addEventListener("drop", (e) => {
          e.preventDefault();
          if (draggedScheduler) {
              const schedulerName = draggedScheduler.textContent.trim();
              const roomNumber = room.getAttribute("data-room");

              // Open modal and populate it
              document.getElementById("modal-scheduler-name").textContent = schedulerName;
              document.getElementById("modal-room-number").textContent = roomNumber;
              scheduleModal.style.display = "block";
          }
      });
  });

  // Modal form submission
  document.getElementById("schedule-form").addEventListener("submit", (e) => {
      e.preventDefault();

      const schedulerName = document.getElementById("modal-scheduler-name").textContent;
      const roomNumber = document.getElementById("modal-room-number").textContent;
      const scheduleDate = document.getElementById("schedule-date").value;
      const startTime = document.getElementById("start-time").value;
      const durationHours = document.getElementById("duration-hours").value;

      // AJAX to handle schedule creation
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
          .then((response) => response.text())
          .then((data) => {
              alert(data); // Display server response
              if (data.includes("success")) {
                  draggedScheduler.remove(); // Remove the scheduler from the pool
                  scheduleModal.style.display = "none";
              }
          })
          .catch((err) => console.error("Error:", err));
          window.location.reload();
  });

  // Close modal
  document.getElementById("cancel-modal").addEventListener("click", () => {
      scheduleModal.style.display = "none";
  });
});

// After successfully adding a schedule, reload the requested schedules
fetchSchedules();

function fetchSchedules() {
    fetch('../../php/fetch-requested-schedules.php')
        .then(response => response.text())
        .then(data => {
            document.getElementById('today-schedules').innerHTML = data;
        })
        .catch(err => console.error("Error fetching schedules:", err));
}

document.querySelectorAll('.view-schedules-btn').forEach(button => {
  button.addEventListener('click', function() {
      const roomId = this.getAttribute('data-room'); // Get room number
      const scheduleList = document.getElementById('schedule-list');
      const roomNumber = document.getElementById('room-number');

      // Set the room number in the modal
      roomNumber.textContent = roomId;

      // Clear the previous schedules
      scheduleList.innerHTML = '';

      // Fetch the schedules for the clicked room using AJAX
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

      // Open the modal
      document.getElementById('view-schedules-modal').style.display = 'block';
  });

    // Close the modal when clicking outside of it or on the close button
    const modal = document.getElementById('view-schedules-modal');
    const closeButton = document.getElementById('close-modal');

    // Close the modal when the close button is clicked
    closeButton.addEventListener('click', function() {
        modal.style.display = 'none'; // Hide the modal
    });

    // Close the modal when clicking outside the modal content
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
});

// Function to fetch schedules for a specific room
function fetchSchedulesForRoom(roomNumber) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'fetchSchedules.php?room=' + roomNumber, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                resolve(JSON.parse(xhr.responseText));
            } else {
                reject('Error fetching schedules');
            }
        };
        xhr.send();
    });
}