// Fetch the current team status from the server (check if user is assigned to a team)
fetch('./system.php')
.then(response => response.json())
.then(data => {
  const teamInfo = document.getElementById('team-info');
  const teamSelection = document.getElementById('team-selection');
  const departmentSelect = document.getElementById('department-select');

  if (data.teamAssigned) {
    // User is assigned to a team, display the department
    teamInfo.innerHTML = `<p>You are already part of the ${data.department} team.</p>`;
  } else {
    // User is not assigned to a team, show the dropdown for department selection
    teamSelection.style.display = 'block';

    // Fetch the list of available teams from the database
    fetch('./get-teams.php')
      .then(response => response.json())
      .then(teams => {
        // Populate the department select dropdown with available teams
        teams.forEach(team => {
          const option = document.createElement('option');
          option.value = team.department;
          option.textContent = team.department;
          departmentSelect.appendChild(option);
        });
      })
      .catch(error => console.log('Error fetching teams:', error));
  }
})
.catch(error => console.log('Error fetching team status:', error));

// Handle the button click to assign the team
document.getElementById('assign-team-btn').addEventListener('click', () => {
const selectedDepartment = document.getElementById('department-select').value;

// Send the selected department to the backend to assign the user to the team
fetch('./assign-team.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ department: selectedDepartment })
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    alert('You have successfully joined the ' + selectedDepartment + ' team!');
    // Update the UI to reflect the new team assignment
    document.getElementById('team-info').innerHTML = `<p>You are already part of the ${selectedDepartment} team.</p>`;
    document.getElementById('team-selection').style.display = 'none'; // Hide the selection dropdown
  } else {
    alert('Error joining the team.');
  }
})
.catch(error => console.log('Error joining the team:', error));
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