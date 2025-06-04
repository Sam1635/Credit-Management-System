<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: signin.html");
    exit;
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
include 'config.php';

// Handle AJAX for getting groups
if (isset($_GET['action']) && $_GET['action'] === 'get_groups') {
    $userid = $_SESSION['userid'];
    $stmt = $conn->prepare("SELECT groupid, groupname FROM user_group WHERE userid = ?");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $groups = [];
    while ($row = $result->fetch_assoc()) {
        $groups[] = $row;
    }
    echo json_encode($groups);
    exit;
}

// Handle AJAX for getting customers by group
if (isset($_GET['action']) && $_GET['action'] === 'get_customers' && isset($_GET['groupid'])) {
    $groupid = $_GET['groupid'];
    $stmt = $conn->prepare("SELECT id, name FROM customers WHERE id IN (SELECT clientid FROM transaction_table WHERE groupid = ?)");
    $stmt->bind_param("i", $groupid);
    $stmt->execute();
    $result = $stmt->get_result();
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    echo json_encode($customers);
    exit;
}

// Handle delete customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_customer') {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM transaction_table WHERE clientid = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt2 = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    echo "Customer and their transactions deleted successfully.";
    exit;
}

// Handle delete group
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_group') {
    $groupid = $_POST['groupid'];
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transaction_table WHERE groupid = ?");
    $stmt->bind_param("i", $groupid);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    if ($count > 0) {
        echo "Cannot delete group: There are customers in this group.";
    } else {
        $stmt2 = $conn->prepare("DELETE FROM user_group WHERE groupid = ?");
        $stmt2->bind_param("i", $groupid);
        $stmt2->execute();
        echo "Group deleted successfully.";
    }
    exit;
}

// Handle add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $userid = $_SESSION['userid'];
    $business_name = $_SESSION['business_name'];  // Assuming business name is saved in session
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $roleid = 2;  // User role as 2

    // Insert new user into the database
    $stmt = $conn->prepare("INSERT INTO admin (userid, businessname, username,password, email,  roleid) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssi", $userid, $business_name, $username,$password, $email,  $roleid);
    if ($stmt->execute()) {
        echo "User added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
    exit;
}

// Handle change group name
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_group_name') {
    $groupid = $_POST['groupid'];
    $new_groupname = $_POST['new_groupname'];

    // Update group name
    $stmt = $conn->prepare("UPDATE user_group SET groupname = ? WHERE groupid = ?");
    $stmt->bind_param("si", $new_groupname, $groupid);
    if ($stmt->execute()) {
        echo "Group name updated successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="settings.css">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Settings</title>
    
</head>
<body>
    <div class="menu-bar">
        <div class="welcome-box">
            <h2>Welcome Customer!</h2>
        </div>
        <div class="btn-container">
            <a href="profile.html"><button class="menu-btn" >Edit Profile</button></a>
            <button class="menu-btn" onclick="openChangeGroupNameModal()">Change Group Name</button>
            <button class="menu-btn" onclick="openGroupModal()">Delete Group</button>
            <button class="menu-btn" onclick="openCustomerModal()">Delete Customer</button>
        </div>
        <div class="logout-btn">
            <form action="logout.php" method="post">
                <button type="submit" class="logout-button">Log Out</button>
            </form>
        </div>
    </div>

    <div class="main-content">
        <button class="back-button" onclick="goBack()">← Back</button>
        <div class="welcome-message">Welcome Customer!</div>
        <p class="quote">"Give credit to whom credit is due."</p>
    </div>

    <!-- Modals -->
    <!-- Change Group Name Modal -->
    <div id="changeGroupNameModal" class="modal">
        <div class="modal-content">
            <h3>Change Group Name</h3>
            <select id="groupSelectForChange">
                <option value="">Select Group</option>
            </select>
            <input type="text" id="newGroupName" placeholder="New Group Name" required>
            <div class="buttons-container">
                <button class="submit-btn" onclick="changeGroupName()">Submit</button>
                <button class="cancel-btn" onclick="closeModal('changeGroupNameModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Delete Customer Modal -->
    <div id="customerModal" class="modal">
        <div class="modal-content">
            <h3>Delete Customer</h3>
            <select id="groupSelect" onchange="loadCustomers(this.value)">
                <option value="">Select Group</option>
            </select>
            <select id="customerSelect">
                <option value="">Select Customer</option>
            </select>
            <div class="delete_btn">
                <button class="delete-btn" onclick="deleteCustomer()">Delete</button>
                <button class="cancel-btn" onclick="closeModal('customerModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Delete Group Modal -->
    <div id="groupModal" class="modal">
        <div class="modal-content">
            <h3>Delete Group</h3>
            <select id="groupDeleteSelect">
                <option value="">Select Group</option>
            </select>
            <div class="delete_btn">
            <button class="delete-btn" onclick="deleteGroup()">Delete</button>
            <button class="cancel-btn" onclick="closeModal('groupModal')">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        function goBack() {
            window.history.back();
        }

        function openChangeGroupNameModal() {
            document.getElementById("changeGroupNameModal").style.display = "flex";
            loadGroupsForChange();
        }

        function loadGroupsForChange() {
            fetch("settings.php?action=get_groups")
                .then(res => res.json())
                .then(data => {
                    const select = document.getElementById("groupSelectForChange");
                    select.innerHTML = '<option value="">Select Group</option>';
                    data.forEach(group => {
                        const option = document.createElement("option");
                        option.value = group.groupid;
                        option.textContent = group.groupname;
                        select.appendChild(option);
                    });
                });
        }

        function changeGroupName() {
            const groupId = document.getElementById("groupSelectForChange").value;
            const newGroupName = document.getElementById("newGroupName").value;

            if (!groupId || !newGroupName) {
                return alert("Please select a group and enter a new name.");
            }

            const formData = new FormData();
            formData.append('action', 'change_group_name');
            formData.append('groupid', groupId);
            formData.append('new_groupname', newGroupName);

            fetch("settings.php", {
                method: "POST",
                body: formData
            }).then(res => res.text())
              .then(msg => {
                  alert(msg);
                  closeModal("changeGroupNameModal");
              });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        function openCustomerModal() {
            document.getElementById("customerModal").style.display = "flex";
            loadGroups("groupSelect");
        }

        function openGroupModal() {
            document.getElementById("groupModal").style.display = "flex";
            loadGroups("groupDeleteSelect");
        }
        
        function loadGroups(selectId) {
            fetch("settings.php?action=get_groups")
                .then(res => res.json())
                .then(data => {
                    const select = document.getElementById(selectId);
                    select.innerHTML = '<option value="">Select Group</option>';
                    data.forEach(group => {
                        const option = document.createElement("option");
                        option.value = group.groupid;
                        option.textContent = group.groupname;
                        select.appendChild(option);
                    });
                });
        }

        function deleteCustomer() {
            const groupId = document.getElementById("groupSelect").value;
            const customerId = document.getElementById("customerSelect").value;
            if (groupId && customerId) {
                const formData = new FormData();
                formData.append('action', 'delete_customer');
                formData.append('id', customerId);

                fetch("settings.php", {
                    method: "POST",
                    body: formData
                }).then(res => res.text())
                  .then(msg => {
                      alert(msg);
                      closeModal("customerModal");
                  });
            }
        }

        function deleteGroup() {
            const groupId = document.getElementById("groupDeleteSelect").value;
            if (groupId) {
                const formData = new FormData();
                formData.append('action', 'delete_group');
                formData.append('groupid', groupId);

                fetch("settings.php", {
                    method: "POST",
                    body: formData
                }).then(res => res.text())
                  .then(msg => {
                      alert(msg);
                      closeModal("groupModal");
                  });
            }
        }
        function loadCustomers(groupId) {
    if (!groupId) {
        document.getElementById("customerSelect").innerHTML = '<option value="">Select Customer</option>';
        return;
    }

    fetch("settings.php?action=get_customers&groupid=" + groupId)
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById("customerSelect");
            select.innerHTML = '<option value="">Select Customer</option>';
            data.forEach(customer => {
                const option = document.createElement("option");
                option.value = customer.id;
                option.textContent = customer.name;
                select.appendChild(option);
            });
        });
}

    </script>
</body>
</html>
