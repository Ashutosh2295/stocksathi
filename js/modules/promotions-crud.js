/**
 * PROMOTIONS MODULE - Firebase CRUD Operations
 * Handles all promotion and coupon management with Firestore
 */

// Initialize promotions list when page loads
document.addEventListener('DOMContentLoaded', async function () {
    await loadPromotions();
    setupPromotionHandlers();
});

/**
 * Load all promotions from Firestore
 */
async function loadPromotions() {
    try {
        const promotionsGrid = document.querySelector('.grid.grid-cols-3');
        if (!promotionsGrid) return;

        // Show loading state
        promotionsGrid.innerHTML = '<div class="col-span-3 text-center py-8">Loading promotions...</div>';

        // Fetch from Firestore
        const promotions = await getAllDocuments('promotions');

        if (promotions.length === 0) {
            promotionsGrid.innerHTML = '<div class="col-span-3 text-center py-8 text-secondary">No promotions found. Create your first promotion!</div>';
            return;
        }

        // Clear and populate
        promotionsGrid.innerHTML = '';
        promotions.forEach(promo => {
            promotionsGrid.innerHTML += createPromotionCard(promo);
        });

        // Attach event listeners
        attachPromotionEvents();
    } catch (error) {
        console.error('Error loading promotions:', error);
        showToast('Failed to load promotions', 'danger');
    }
}

/**
 * Create promotion card HTML
 */
function createPromotionCard(promo) {
    const statusBadge = getStatusBadge(promo.status);
    const icon = getPromotionIcon(promo.type);

    return `
        <div class="card">
            <div class="card-body">
                <div class="flex justify-between items-start mb-4">
                    <div style="font-size: 3rem;">${icon}</div>
                    ${statusBadge}
                </div>
                <h3 class="text-lg font-semibold mb-2">${promo.name}</h3>
                <p class="text-secondary text-sm mb-4">${promo.description || ''}</p>
                <div class="mb-3 pb-3" style="border-bottom: 1px solid var(--border-light);">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-secondary">Discount:</span>
                        <span class="font-semibold text-primary">${promo.discountValue}${promo.discountType === 'percentage' ? '%' : '₹'} OFF</span>
                    </div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-secondary">Code:</span>
                        <code>${promo.code}</code>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-secondary">Valid Until:</span>
                        <span>${formatDate(promo.endDate)}</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button class="btn btn-outline btn-sm flex-1" onclick="editPromotion('${promo.id}')">Edit</button>
                    <button class="btn btn-ghost btn-sm" onclick="deletePromotion('${promo.id}')">🗑️</button>
                </div>
            </div>
        </div>
    `;
}

/**
 * Setup promotion form handlers
 */
function setupPromotionHandlers() {
    const form = document.querySelector('#addPromotionModal form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await createPromotion();
        });
    }
}

/**
 * Create new promotion in Firestore
 */
async function createPromotion() {
    try {
        const modal = document.getElementById('addPromotionModal');
        const form = modal.querySelector('form');

        const promotionData = {
            name: form.querySelector('input[placeholder="Enter promotion name"]').value,
            description: form.querySelector('textarea').value,
            discountType: form.querySelector('select').value.toLowerCase().replace(' ', '_'),
            discountValue: parseFloat(form.querySelectorAll('input[type="number"]')[0].value),
            code: form.querySelectorAll('input[type="text"]')[1].value.toUpperCase(),
            startDate: form.querySelectorAll('input[type="date"]')[0].value,
            endDate: form.querySelectorAll('input[type="date"]')[1].value,
            status: 'active',
            type: 'coupon',
            usageCount: 0
        };

        // Save to Firestore
        const docId = await addDocument('promotions', promotionData);

        console.log('Promotion created:', docId);
        showToast('Promotion created successfully!', 'success');
        closeModal('addPromotionModal');
        form.reset();

        // Reload promotions
        await loadPromotions();
    } catch (error) {
        console.error('Error creating promotion:', error);
        showToast('Failed to create promotion', 'danger');
    }
}

/**
 * Edit promotion
 */
async function editPromotion(id) {
    try {
        const promo = await getDocument('promotions', id);
        // TODO: Populate form and show modal
        showToast('Edit functionality - Coming soon!', 'info');
    } catch (error) {
        console.error('Error loading promotion:', error);
        showToast('Failed to load promotion', 'danger');
    }
}

/**
 * Delete promotion from Firestore
 */
async function deletePromotion(id) {
    if (!confirm('Are you sure you want to delete this promotion?')) return;

    try {
        await deleteDocument('promotions', id);
        showToast('Promotion deleted successfully!', 'success');
        await loadPromotions();
    } catch (error) {
        console.error('Error deleting promotion:', error);
        showToast('Failed to delete promotion', 'danger');
    }
}

/**
 * Helper functions
 */
function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge badge-success">Active</span>',
        'scheduled': '<span class="badge badge-primary">Scheduled</span>',
        'expired': '<span class="badge badge-danger">Expired</span>'
    };
    return badges[status] || badges.active;
}

function getPromotionIcon(type) {
    const icons = {
        'coupon': '🎁',
        'sale': '💸',
        'bulk': '📦',
        'seasonal': '🏷️'
    };
    return icons[type] || '🎁';
}
