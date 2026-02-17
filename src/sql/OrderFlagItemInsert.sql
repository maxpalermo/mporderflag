INSERT INTO
    `{$pfx}order_flag_item` (
        `id_order_flag_item`,
        `name`,
        `icon`,
        `color`,
        `date_add`,
        `date_upd`
    )
VALUES
    (
        1,
        'OK',
        'verified',
        '#70b580',
        '2025-04-14 16:49:24',
        NULL
    ),
    (
        2,
        'ATTENZIONE',
        'warning',
        '#e9bd0c',
        '2025-04-14 16:49:47',
        NULL
    ),
    (
        3,
        'ERRORE',
        'error',
        '#f54c3e',
        '2025-04-14 16:50:13',
        NULL
    ),
    (
        4,
        'VERIFICA PAGAMENTO',
        'credit_score',
        '#25b9d7',
        '2025-04-14 16:50:36',
        NULL
    );