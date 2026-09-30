<style>
    /* Usa variables del layout del portal (--text, --muted, --border, --white). */
    .profile-form { padding: 1.5rem; }
    .profile-form .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 1rem 1.25rem;
        margin-bottom: 1rem;
    }
    .profile-form label {
        display: block;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--muted);
        margin-bottom: 0.35rem;
    }
    .profile-form .checkbox-row label {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text);
        margin-bottom: 0;
    }
    .profile-form input[type="text"],
    .profile-form input[type="email"],
    .profile-form input[type="tel"],
    .profile-form input[type="password"],
    .profile-form select,
    .profile-form textarea {
        width: 100%;
        padding: 0.55rem 0.75rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 0.9rem;
        color: var(--text);
        background: var(--white);
    }
    .profile-form textarea { min-height: 88px; resize: vertical; }
    .profile-form input:focus,
    .profile-form select:focus,
    .profile-form textarea:focus {
        outline: none;
        border-color: var(--green);
    }
    .profile-form .form-hint {
        font-size: 0.75rem;
        color: var(--muted);
        margin-top: 0.25rem;
    }
    .profile-form .form-section-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text);
        margin: 1.25rem 0 0.75rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border);
    }
    .profile-form .form-section-title:first-child {
        margin-top: 0;
        padding-top: 0;
        border-top: none;
    }
    .profile-form .checkbox-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0.75rem 0;
    }
    .profile-form .form-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border);
    }
    .profile-alert {
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }
    .profile-alert-success {
        background: #dcfce7;
        color: #166534;
    }
    .profile-alert-error {
        background: #fee2e2;
        color: #991b1b;
    }
</style>
