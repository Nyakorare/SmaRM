// Assign team
function assignTeam(userId) {
    const team = document.getElementById(`team-select-${userId}`).value;
    if (team) {
      fetch('../../php/manage-teams.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'assign', user_id: userId, team })
      })
      .then(response => response.json())
      .then(data => {
        alert(data.message);
        location.reload();
      });
    }
  }

  // Change team
  function changeTeam(userId) {
    const team = document.getElementById(`team-select-${userId}`).value;
    if (team) {
      fetch('../../php/manage-teams.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'change', user_id: userId, team })
      })
      .then(response => response.json())
      .then(data => {
        alert(data.message);
        location.reload();
      });
    }
  }

  // Delete from team
  function deleteFromTeam(userId) {
    if (confirm('Are you sure you want to remove this user from the team?')) {
      fetch('../../php/manage-teams.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', user_id: userId })
      })
      .then(response => response.json())
      .then(data => {
        alert(data.message);
        location.reload();
      });
    }
  }

  // Add new team
  function addTeam() {
    const teamName = document.getElementById('new-team-name').value.trim();
    if (teamName) {
      fetch('../../php/manage-teams.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'add_team', team: teamName })
      })
      .then(response => response.json())
      .then(data => {
        alert(data.message);
        location.reload();
      });
    }
  }

  // Delete team
  function deleteTeam(teamName) {
    if (confirm(`Are you sure you want to delete the team "${teamName}"?`)) {
      fetch('../../php/manage-teams.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete_team', team: teamName })
      })
      .then(response => response.json())
      .then(data => {
        alert(data.message);
        location.reload();
      });
    }
  }

  // Edit team name
function editTeam(teamName) {
const newTeamName = prompt('Enter the new name for the team:', teamName);
if (newTeamName && newTeamName !== teamName) {
  fetch('../../php/manage-teams.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'update_team', old_team: teamName, new_team: newTeamName })
  })
  .then(response => response.json())
  .then(data => {
    alert(data.message);
    location.reload();  // Refresh the page to reflect the updated data
  });
}
}

// Handle the response and display errors
fetch('../../php/manage-teams.php', {
method: 'POST',
headers: { 'Content-Type': 'application/json' },
body: JSON.stringify({ action: 'update_team', old_team: oldName, new_team: newName })
})
.then(response => response.json())
.then(data => {
if (!data.success) {
  alert(data.message);  // Show error message if action fails
} else {
  alert(data.message);  // Show success message
  location.reload();  // Refresh page to show updated data
}
});

function updateRequestStatus(requestId, status) {
  var xhr = new XMLHttpRequest();
  xhr.open("POST", "../../php/update_request_status.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.onload = function() {
      if (xhr.status === 200) {
          alert("Request " + status);
          location.reload(); // Reload the page to show updated status
      }
  };
  xhr.send("request_id=" + requestId + "&status=" + status);
}