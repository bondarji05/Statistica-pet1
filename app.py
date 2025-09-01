# ЗАПУСК:
# --- Импорты библиотек ---
import pandas as pd  # работа с таблицами/SQL-результатами
import plotly.express as px  # красивые интерактивные графики
import streamlit as st  # сам фреймворк веб-приложения
from sqlalchemy import create_engine, text

# --- Настройки подключения к БД ---
DB_HOST = "localhost"  # адрес MySQL-сервера
DB_PORT = 3306  # порт MySQL
DB_USER = "root"  # пользователь БД
DB_PASS = ""  # пароль
DB_NAME = "PET_1"  # база данных

# --- Базовая конфигурация страницы Streamlit ---
st.set_page_config(
    page_title="HR Dashboard",  # заголовок вкладки браузера
    layout="wide",  # широкая вёрстка на всю ширину
)

# --- Функция создания подключения к MySQL через SQLAlchemy ---
def get_engine():
    # Формируем URL подключения вида:
    # mysql+pymysql://USER:PASS@HOST:PORT/DB?charset=utf8mb4
    url = (
        f"mysql+pymysql://{DB_USER}:{DB_PASS}"
        f"@{DB_HOST}:{DB_PORT}/{DB_NAME}?charset=utf8mb4"
    )
    # pool_pre_ping=True пингует соединение, чтобы переоткрыть при обрыве
    return create_engine(url, pool_pre_ping=True)


# Создаём «движок» подключения один раз
engine = get_engine()

# --- Заголовок приложения ---
st.title("Аналитика")

# --- Блок фильтров: получаем список отделов из БД ---
try:
    # Берём уникальные department, исключаем NULL, сортируем
    departments_df = pd.read_sql(
        "SELECT DISTINCT department FROM profiles WHERE department IS NOT NULL ORDER BY department",
        engine,
    )
    departments = departments_df["department"].dropna().tolist()  # в список Python
except Exception as e:
    # Если упали на коннекте/запросе — покажем ошибку и остановим приложение
    st.error(f"Ошибка подключения или запроса к БД: {e}")
    st.stop()

# Рисуем два выпадающих списка — «Отдел» и «Статус»
col1, col2 = st.columns(2)  # две колонки рядом
dep = col1.selectbox("Отдел", ["Все"] + departments, index=0)
status = col2.selectbox("Статус", ["Все", "working", "dismissed"], index=0)

# --- Таблица сотрудников с учётом выбранных фильтров ---
# Базовый SELECT — поля, которые хотим показать
base_q = "SELECT user_id, name, gender, department, status, birth_date, created_at FROM profiles"
conds, params = [], {}  # сюда соберём WHERE-условия и их параметры

# Если отдел выбран конкретный — добавим условие department = :dep
if dep != "Все":
    conds.append("department = :dep")
    params["dep"] = dep

if status != "Все":
    conds.append("status = :st")
    params["st"] = status

# Если есть условия — соберём строку WHERE ... AND ...
if conds:
    base_q += " WHERE " + " AND ".join(conds)

# Отсортируем по дате создания (новые сверху) и ограничим до 1000 строк
base_q += " ORDER BY created_at DESC LIMIT 1000"

# Выполняем SQL с безопасными параметрами (через sqlalchemy.text)
df = pd.read_sql(text(base_q), engine, params=params)

# Показываем таблицу
st.subheader("Список сотрудников")
st.dataframe(
    df, hide_index=True, width="stretch"
)  # width="stretch" — новая замена use_container_width

# Разделительная линия
st.markdown("---")

# --- График 1: Пирог — кто работает по отделам ---
st.subheader("Распределение сотрудников по отделам (active)")
pie = pd.read_sql(
    """
    SELECT department, COUNT(*) AS cnt
    FROM profiles
    WHERE status='working'
    GROUP BY department
    ORDER BY cnt DESC
""",
    engine,
)

if pie.empty:
    st.info("Нет данных для построения пирога.")
else:
    # px.pie строит круговую диаграмму по колонкам names/values
    fig_pie = px.pie(pie, names="department", values="cnt", title="Активные по отделам")
    st.plotly_chart(fig_pie, width="stretch")

# --- График 2: Стек-гистограмма — гендерный состав работающих сотрудников по отделам ---
st.subheader("Гендерное распределение по отделам ")
gender = pd.read_sql(
    """
    SELECT department, gender, COUNT(*) AS cnt
    FROM profiles
    WHERE status='working'
    GROUP BY department, gender
    ORDER BY department, gender
""",
    engine,
)

if gender.empty:
    st.info("Нет данных для гендерного графика.")
else:
    # barmode="stack" складывает колонки друг на друга по цвету (gender)
    fig_bar = px.bar(
        gender,
        x="department",
        y="cnt",
        color="gender",
        barmode="stack",
        title="Гендерный состав",
    )
    st.plotly_chart(fig_bar, width="stretch")

# --- График 3: Линия — новые профили по годам  ---
st.subheader("Число новых сотрудников по годам")
yearly = pd.read_sql(
    """
    SELECT YEAR(created_at) AS year, COUNT(*) AS hires
    FROM profiles
    GROUP BY YEAR(created_at)
    ORDER BY year
""",
    engine,
)

if yearly.empty:
    st.info("Нет данных для динамики по годам.")
else:
    # markers=True — точки на линии
    fig_line_hires = px.line(
        yearly, x="year", y="hires", markers=True, title=""
    )
    st.plotly_chart(fig_line_hires, width="stretch")
