<div class="header"><h1>Roles &amp; Access</h1></div>

<div class="grid" style="margin-bottom:20px;">
  <div class="card"><h3>Total Admin Users</h3><p class="stat-value" id="roles-total-admins">—</p><p class="stat-status">With system access</p></div>
  <div class="card"><h3>Super Admins</h3><p class="stat-value" id="roles-super-admins">—</p><p class="stat-status">Full access</p></div>
  <div class="card"><h3>Staff Accounts</h3><p class="stat-value" id="roles-staff">—</p><p class="stat-status">Limited access</p></div>
  <div class="card"><h3>Active Trainers</h3><p class="stat-value" id="roles-trainers">—</p><p class="stat-status">Schedule access</p></div>
</div>

<div class="option-bar">
  <button id="addUserBtn">+ Add Admin User</button>
</div>

<div class="table-wrap">
  <table id="usersTable">
    <thead>
      <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr>
    </thead>
    <tbody><tr><td colspan="6" class="loading">Loading…</td></tr></tbody>
  </table>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
  <div class="modal-box">
    <h3>Add New Admin User</h3>
    <form id="addUserForm">
      <div class="form-grid">
        <div class="form-group"><label>First Name</label><input type="text" name="first_name" required placeholder="First name"/></div>
        <div class="form-group"><label>Last Name</label><input type="text" name="last_name" required placeholder="Last name"/></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="admin@societyfitness.com"/></div>
        <div class="form-group">
          <label>Role</label>
          <select name="role" required>
            <option value="">Select Role</option>
            <option value="super_admin">Super Admin</option>
            <option value="admin">Admin</option>
            <option value="staff">Staff</option>
          </select>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label>Temporary Password</label>
          <input type="password" name="temp_password" required placeholder="Min 8 characters" autocomplete="new-password"/>
          <small style="color:#888;">User should change on first login.</small>
        </div>
      </div>
      <div class="modal-actions">
        <button type="submit">Create User</button>
        <button type="button" class="btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
  <div class="modal-box">
    <h3>Edit User</h3>
    <form id="editUserForm">
      <input type="hidden" id="edit_user_id" name="user_id"/>
      <div class="form-grid">
        <div class="form-group">
          <label>Role</label>
          <select id="edit_user_role" name="role">
            <option value="super_admin">Super Admin</option>
            <option value="admin">Admin</option>
            <option value="staff">Staff</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="edit_user_status" name="status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label>New Password <small style="color:#888;">(leave blank to keep current)</small></label>
          <input type="password" name="new_password" placeholder="New password" autocomplete="new-password"/>
        </div>
      </div>
      <div class="modal-actions">
        <button type="submit">Save Changes</button>
        <button type="button" class="btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>
