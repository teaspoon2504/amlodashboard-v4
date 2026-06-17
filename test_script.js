function openApprovalModal(submissionId, action, officerName) {
    document.getElementById('modal-submission-id').value = submissionId;
    document.getElementById('modal-action').value = action;
    document.getElementById('modal-catatan').value = '';
    
    const title = document.getElementById('modal-title');
    const desc = document.getElementById('modal-desc');
    const btn = document.getElementById('btn-submit-modal');
    
    if (action === 'approve') {
        title.innerHTML = '<i data-lucide="check-circle" ></i> Konfirmasi Approve';
        desc.innerHTML = `Anda akan menyetujui pengajuan tugas dari <b>${officerName}</b>. Status tugas ini akan berubah menjadi <b>Selesai</b>.`;
        btn.textContent = 'Approve Tugas';
        btn.style.background = 'var(--success)';
    } else {
        title.innerHTML = '<i class="fa-solid fa-times-circle" style="color:var(--critical);"></i> Konfirmasi Reject';
        desc.innerHTML = `Anda akan menolak (reject) pengajuan tugas dari <b>${officerName}</b>.`;
        btn.textContent = 'Reject Tugas';
        btn.style.background = 'var(--critical)';
    }
    
    document.getElementById('modal-overlay').classList.add('open');
}

function closeModal() {
    document.getElementById('modal-overlay').classList.remove('open');
}

function submitApproval() {
    const submissionId = document.getElementById('modal-submission-id').value;
    const action = document.getElementById('modal-action').value;
    const catatan = document.getElementById('modal-catatan').value;
    const btn = document.getElementById('btn-submit-modal');
    
    btn.disabled = true;
    btn.textContent = 'Memproses...';
    
    fetch('../api/approvals.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: action,
            submission_id: submissionId,
            catatan: catatan
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeModal();
            // Remove row from table with a nice fade out
            const row = document.getElementById('row-' + submissionId);
            if (row) {
                row.style.transition = 'opacity 0.4s ease';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    // Check if table is empty
                    const tbody = document.getElementById('approvals-tbody');
                    if (tbody.children.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="7" style="text-align:center;color:var(--steel);padding:40px">
                                    <div style="font-size:24px;margin-bottom:8px"><i class="fa-solid fa-gift"></i></div>
                                    Tidak ada pengajuan yang perlu di-review saat ini.
                                </td>
                            </tr>
                        `;
                    }
                }, 400);
            }
        } else {
            alert('Gagal memproses: ' + (data.message || 'Error tidak diketahui'));
            btn.disabled = false;
            btn.textContent = action === 'approve' ? 'Approve Tugas' : 'Reject Tugas';
        }
    })
    .catch(err => {
        alert('Terjadi kesalahan koneksi jaringan.');
        console.error(err);
        btn.disabled = false;
        btn.textContent = action === 'approve' ? 'Approve Tugas' : 'Reject Tugas';
    });
}
