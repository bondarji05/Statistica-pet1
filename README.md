#  PET Project — Статистика сотрудников

Это мой первый pet-проект   
Простое веб-приложение для работы с профилями сотрудников: регистрация, авторизация и аналитика по отделам.


##  Используемые технологии
- **PHP 8+** — регистрация, авторизация, управление профилями  
- **MySQL** — хранение данных  
- **Bootstrap 5.3** — стилизация интерфейса  


## 📂 Структура проекта

project/
│── index.php        # Форма входа
│── register.php     # Обработка регистрации
│── vhod.php         # Авторизация
│── form.php         # Форма создания профиля
│── db.php           # Подключение к базе данных
│── app.py           # Аналитика (Streamlit + графики)
│── /img/            # Скриншоты и картинки


## 🗄️ База данных
В проекте используются три основные таблицы:
- **users** — учетные записи (email, пароль, дата регистрации)  
- **profiles** — профили сотрудников (ФИО, пол, отдел, статус, дата создания)  


---

## ▶️ Запуск PHP-части
1. Установить **OpenServer**.  
2. Скопировать проект в папку `htdocs` (или аналогичную).  
3. Импортировать базу данных:  
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
4. Настроить `db.php` (логин/пароль MySQL).  
5. Перейти в браузере на [http://localhost/pet1/index.php](http://localhost/pet1/index.php).

---

## ▶️ Запуск аналитики (Streamlit)
1. Установить зависимости:
   ```bash
   pip install pandas plotly streamlit sqlalchemy pymysql
   ```
2. Настроить `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` в `app.py`.  
3. Запустить Streamlit:
   ```bash
   streamlit run app.py
   ```
4. Перейти в браузере на [http://localhost:8501](http://localhost:8501).

---

## 📸 Скриншоты
Форма регистрации
<img width="1910" height="915" alt="image" src="https://github.com/user-attachments/assets/dbcdfaec-a5ef-496b-b4f7-b696edbd41bd" /> 


Страница с аналитикой
<img width="1920" height="3182" alt="screencapture-localhost-8501-2025-09-01-21_58_57" src="https://github.com/user-attachments/assets/9f2ac0e0-3a30-4132-91be-264997cabb94" />  


Анкета сотрудника при нажатии на кнопку - Зарегистрироваться 
<img width="1920" height="1178" alt="screencapture-pet1-form-php-2025-09-01-22_01_17" src="https://github.com/user-attachments/assets/24977119-ce7f-4dac-a831-df4af48e6a1d" />

