// In the displayRentals function, add a Return button for active rentals
if ($rental['status'] === 'active') {
    $returnButton = `
        <button class="btn btn-sm btn-success return-btn" data-id="${rental['rental_id']}">
            <i class="fas fa-undo-alt"></i> Return Vehicle
        </button>
    `;
} else {
    $returnButton = '';
}

// Then add it to the actions column
html += `
    <td>
        <a href="rental-details.php?id=${rental['rental_id']}" class="btn btn-sm btn-secondary me-2">
            <i class="fas fa-eye"></i> View
        </a>
        ${cancelButton}
        ${returnButton}
    </td>
`;