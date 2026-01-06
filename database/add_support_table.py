import pymysql

connection = pymysql.connect(
    host='localhost',
    port=3306,
    user='root',
    password='1111',
    database='construkt'
)

cursor = connection.cursor()
cursor.execute("""
CREATE TABLE IF NOT EXISTS support_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id INT UNSIGNED NOT NULL,
    manager_id INT UNSIGNED NULL,
    message TEXT NOT NULL,
    is_from_customer TINYINT(1) NOT NULL DEFAULT 1,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX fk_support_customer_idx (customer_id),
    INDEX fk_support_manager_idx (manager_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
""")
connection.commit()
print('support_messages table created!')
connection.close()
