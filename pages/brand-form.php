<?php
/**
 * Brand Form - Add/Edit Brands
 */
require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($_GET['id']) ? 'Edit' : 'Add' ?> Brand - Stocksathi</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/layout.css">
    <script src="<?= JS_PATH ?>/toast.js"></script>
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/../_includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../_includes/header.php'; ?>
            
            <main class="content">
                <div class="content-header">
                    <nav class="breadcrumb">
                        <a href="<?= BASE_PATH ?>/index.php" class="breadcrumb-item">Home</a>
                        <span class="breadcrumb-separator">/</span>
                        <a href="<?= BASE_PATH ?>/pages/brands.php" class="breadcrumb-item">Brands</a>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active"><?= isset($_GET['id']) ? 'Edit' : 'Add' ?> Brand</span>
                    </nav>
                    <h1 class="content-title"><?= isset($_GET['id']) ? 'Edit' : 'Add New' ?> Brand</h1>
                </div>

                <form id="brandForm">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Brand Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label required">Brand Name</label>
                                <input type="text" id="brandName" name="name" class="form-control"
                                    placeholder="Enter brand name" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea id="brandDescription" name="description" class="form-control"
                                    placeholder="Brand description" rows="3"></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Website</label>
                                <input type="url" id="brandWebsite" name="website" class="form-control"
                                    placeholder="https://example.com">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select id="brandStatus" name="status" class="form-control">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 justify-end mt-6">
                        <a href="<?= BASE_PATH ?>/pages/brands.php" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="saveBtn">Save Brand</button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="<?= JS_PATH ?>/api-client.js"></script>
    <script src="<?= JS_PATH ?>/app.js"></script>
    <script>
        let isEditMode = false;
        let brandId = null;

        document.addEventListener('DOMContentLoaded', async function() {
            const urlParams = new URLSearchParams(window.location.search);
            brandId = urlParams.get('id');

            if (brandId) {
                isEditMode = true;
                await loadBrand(brandId);
            }
        });

        async function loadBrand(id) {
            try {
                const response = await ApiClient.get(`/brands/read.php?id=${id}`);
                if (response.success && response.data) {
                    const brand = response.data;
                    document.getElementById('brandName').value = brand.name || '';
                    document.getElementById('brandDescription').value = brand.description || '';
                    document.getElementById('brandWebsite').value = brand.website || '';
                    document.getElementById('brandStatus').value = brand.status || 'active';
                }
            } catch (error) {
                Toast.error('Failed to load brand: ' + error.message);
            }
        }

        document.getElementById('brandForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = {
                name: document.getElementById('brandName').value.trim(),
                description: document.getElementById('brandDescription').value.trim() || null,
                website: document.getElementById('brandWebsite').value.trim() || null,
                status: document.getElementById('brandStatus').value
            };

            if (!formData.name) {
                Toast.error('Brand name is required');
                return;
            }

            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            try {
                let response;
                if (isEditMode && brandId) {
                    formData.id = brandId;
                    response = await ApiClient.put('/brands/update.php', formData);
                } else {
                    response = await ApiClient.post('/brands/create.php', formData);
                }

                if (response.success) {
                    Toast.success(response.message || 'Brand saved successfully');
                    setTimeout(() => {
                        window.location.href = '<?= BASE_PATH ?>/pages/brands.php';
                    }, 1000);
                } else {
                    Toast.error(response.message || 'Failed to save brand');
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save Brand';
                }
            } catch (error) {
                Toast.error(error.message || 'Failed to save brand');
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Brand';
            }
        });
    </script>
</body>
</html>
