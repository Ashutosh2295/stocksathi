<!-- Add/Edit Modal Template -->
<div class="modal-backdrop" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add New Item</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addForm">
                <div class="form-group">
                    <label class="form-label required">Item Name</label>
                    <input type="text" class="form-control" placeholder="Enter name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" placeholder="Enter description"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button>
            <button type="submit" form="addForm" class="btn btn-primary">Save</button>
        </div>
    </div>
</div>

<!-- View Modal Template -->
<div class="modal-backdrop" id="viewModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Item Details</h3>
            <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display: grid; gap: 16px;">
                <div>
                    <strong>Name:</strong>
                    <p id="viewName">Item Name</p>
                </div>
                <div>
                    <strong>Description:</strong>
                    <p id="viewDescription">Description text</p>
                </div>
                <div>
                    <strong>Status:</strong>
                    <span class="badge badge-success" id="viewStatus">Active</span>
                </div>
                <div>
                    <strong>Created:</strong>
                    <p id="viewCreated">2024-12-23</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<!-- Edit Modal Template -->
<div class="modal-backdrop" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Item</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editForm">
                <input type="hidden" id="editId">
                <div class="form-group">
                    <label class="form-label required">Item Name</label>
                    <input type="text" class="form-control" id="editName" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" id="editDescription"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-control" id="editStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">Cancel</button>
            <button type="submit" form="editForm" class="btn btn-primary">Update</button>
        </div>
    </div>
