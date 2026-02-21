/**
 * DASHBOARD - Real-time Statistics
 */

document.addEventListener('DOMContentLoaded', async function () {
    console.log('📊 Dashboard Loading...');
    await loadDashboardStats();
});

async function loadDashboardStats() {
    try {
        // Get counts from all collections
        const [products, customers, promotions] = await Promise.all([
            window.crudOperations.count('products'),
            window.crudOperations.count('customers'),
            window.crudOperations.count('promotions')
        ]);

        // Update stat cards
        document.querySelector('#totalProducts').textContent = products || 0;
        document.querySelector('#totalCustomers').textContent = customers || 0;
        document.querySelector('#totalPromotions').textContent = promotions || 0;


        console.log('✅ Dashboard stats loaded');
    } catch (error) {
        console.error('❌ Dashboard error:', error);
    }
}

console.log('✅ Dashboard Ready');
