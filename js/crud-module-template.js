/**
 * UNIVERSAL CRUD MODULE TEMPLATE
 * Copy this pattern to any module that needs full CRUD operations
 * 
 * Usage:
 * 1. Replace 'items' with your collection name
 * 2. Replace 'Item' with your display name
 * 3. Replace 'itemsTable' with your table ID
 * 4. Customize form fields as needed
 */

// ============================================
// LOAD & DISPLAY
// ============================================

async function loadItems() {
    try {
        if (!window.firestoreHelpers) {
            console.error('firestoreHelpers not available');
            return [];
        }
        const items = await window.firestoreHelpers.getAllDocuments('items');
        displayItems(items);
        return items;
    } catch (error) {
        console.error('Error loading items:', error);
        if (window.showToast) {
            window.showToast('Failed to load items', 'danger');
        }
        return [];
    }
}

function displayItems(items) {
    const tbody = document.querySelector('#itemsTable tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-secondary">No items found. Click "Add Item" to get started.</td></tr>';
        return;
    }

    items.forEach(item => {
        const row = createItemRow(item);
        tbody.appendChild(row);
    });
}

function createItemRow(item) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>${item.name || 'N/A'}</td>
        <td>${item.description || 'No description'}</td>
        <td>${item.status || 'Active'}</td>
        <td class="table-actions">
            <button class="btn btn-ghost btn-sm" onclick="editItem('${item.id}')" title="Edit">
                <i data-lucide="pencil"></i>
            </button>
            <button class="btn btn-ghost btn-sm" onclick="deleteItem('${item.id}', '${(item.name || 'Item').replace(/'/g, "\\'")}')" title="Delete">
                <i data-lucide="trash-2"></i>
            </button>
        </td>
    `;
    return tr;
}

// ============================================
// CREATE
// ============================================

async function addItem(itemData) {
    try {
        if (!window.firebaseDb && !window.firestoreHelpers) {
            throw new Error('Firebase not initialized');
        }

        const docData = {
            ...itemData,
            createdAt: firebase.firestore.FieldValue.serverTimestamp(),
            updatedAt: firebase.firestore.FieldValue.serverTimestamp()
        };

        let docId;
        if (window.firebaseDb) {
            const docRef = await window.firebaseDb.collection('items').add(docData);
            docId = docRef.id;
        } else if (window.firestoreHelpers && window.firestoreHelpers.addDocument) {
            docId = await window.firestoreHelpers.addDocument('items', docData);
        } else {
            throw new Error('Add functionality not available');
        }

        if (window.showToast) {
            window.showToast('Item added successfully!', 'success');
        }
        await loadItems();
        return docId;
    } catch (error) {
        console.error('Error adding item:', error);
        if (window.showToast) {
            window.showToast('Failed to add item', 'danger');
        }
        throw error;
    }
}

// ============================================
// UPDATE
// ============================================

async function updateItem(itemId, itemData) {
    try {
        if (window.crudUniversal && window.crudUniversal.updateDocument) {
            await window.crudUniversal.updateDocument('items', itemId, itemData, itemData.name || 'Item', () => {
                loadItems();
            });
        } else if (window.firestoreHelpers && window.firestoreHelpers.updateDocument) {
            await window.firestoreHelpers.updateDocument('items', itemId, {
                ...itemData,
                updatedAt: firebase.firestore.FieldValue.serverTimestamp()
            });
            if (window.showToast) {
                window.showToast('Item updated successfully!', 'success');
            }
            await loadItems();
        } else {
            throw new Error('Update functionality not available');
        }
    } catch (error) {
        console.error('Error updating item:', error);
        if (window.showToast) {
            window.showToast('Failed to update item', 'danger');
        }
        throw error;
    }
}

// ============================================
// DELETE
// ============================================

async function deleteItem(itemId, itemName = null) {
    try {
        if (!itemName) {
            const item = await window.firestoreHelpers.getDocument('items', itemId);
            itemName = item.name || 'Item';
        }

        if (window.crudUniversal && window.crudUniversal.deleteDocument) {
            await window.crudUniversal.deleteDocument('items', itemId, itemName, () => {
                loadItems();
            });
        } else {
            if (!confirm(`Are you sure you want to delete "${itemName}"?`)) {
                return;
            }
            await window.firestoreHelpers.deleteDocument('items', itemId);
            if (window.showToast) {
                window.showToast('Item deleted successfully!', 'success');
            }
            await loadItems();
        }
    } catch (error) {
        console.error('Error deleting item:', error);
        if (window.showToast) {
            window.showToast('Failed to delete item', 'danger');
        }
    }
}

// ============================================
// EDIT
// ============================================

async function editItem(itemId) {
    try {
        if (window.crudUniversal && window.crudUniversal.editDocument) {
            await window.crudUniversal.editDocument('items', itemId, 'item-form.html');
        } else {
            window.location.href = `item-form.html?id=${itemId}`;
        }
    } catch (error) {
        console.error('Error editing item:', error);
        if (window.showToast) {
            window.showToast('Failed to load item for editing', 'danger');
        }
    }
}

// ============================================
// INITIALIZE
// ============================================

document.addEventListener('DOMContentLoaded', async () => {
    if (document.getElementById('itemsTable')) {
        setTimeout(async () => {
            if (!window.firebaseDb && !window.firestoreHelpers) {
                console.warn('⚠️ Firebase not initialized yet, retrying...');
                setTimeout(async () => {
                    await loadItems();
                    if (window.firestoreHelpers) {
                        window.firestoreHelpers.listenToCollection('items', (items) => {
                            displayItems(items);
                        });
                    }
                }, 1000);
                return;
            }
            
            await loadItems();
            if (window.firestoreHelpers && window.firestoreHelpers.listenToCollection) {
                window.firestoreHelpers.listenToCollection('items', (items) => {
                    displayItems(items);
                });
            }
        }, 500);
    }
});

// ============================================
// EXPORT
// ============================================

window.itemsModule = {
    loadItems,
    addItem,
    updateItem,
    deleteItem,
    editItem
};

// Make functions globally available
window.loadItems = loadItems;
window.deleteItem = deleteItem;
window.editItem = editItem;

