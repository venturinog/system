BEGIN TRANSACTION;

-- Health Center: resultados cacheados de los chequeos "lentos" y bitácora de
-- cada corrida del recolector (helper privileged healthcollect).
-- Ver DASHBOARDtodo.md §6.

CREATE TABLE health_check (
    check_id    varchar(96)  primary key,
    group_id    varchar(48)  not null,
    category    varchar(32)  not null,
    severity    varchar(16)  not null,
    value       varchar(128),
    threshold   varchar(64),
    detail      varchar(255),
    link        varchar(255),
    action_id   varchar(64),
    source      varchar(64),
    error       varchar(255),
    checked_at  integer      not null,
    -- Mensajes traducibles: clave + args (JSON). `detail`/`threshold` guardan
    -- el inglés de respaldo. En upgrade los agrega _ensureSchema() (health.db
    -- es caché); ver DASHBOARDtodo.md §17.
    detail_key    varchar(64),
    detail_args   varchar(512),
    detail_count  integer,
    threshold_key  varchar(64),
    threshold_args varchar(255)
);

CREATE TABLE health_run (
    id          integer  primary key,
    started_at  integer  not null,
    duration_ms integer  not null default 0,
    ran         integer  not null default 0,
    skipped     integer  not null default 0,
    errors      integer  not null default 0,
    message     varchar(255)
);

COMMIT;
