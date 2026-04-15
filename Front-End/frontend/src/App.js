import { useEffect } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { fetchMe } from './features/auth/authSlice';

import LoginPage       from './pages/auth/LoginPage';
import DashboardPage   from './pages/admin/DashboardPage';
import UsersPage       from './pages/admin/UsersPage';
import EquipesPage     from './pages/admin/EquipesPage';
import TachesPage      from './pages/admin/TachesPage';
import PlanningsPage   from './pages/admin/PlanningsPage';
import ReposPage       from './pages/admin/ReposPage';
import MonPlanningPage from './pages/employe/MonPlanningPage';
import MesReposPage    from './pages/employe/MesReposPage';
import ProtectedRoute  from './components/ProtectedRoute';
import AdminRoute      from './components/AdminRoute';

export default function App() {
    const dispatch = useDispatch();
    const { token } = useSelector((state) => state.auth);

    useEffect(() => {
        if (token) dispatch(fetchMe());
    }, [token]);

    return (
        <Routes>
            <Route path="/login" element={<LoginPage />} />

            {/* Protected — kull authenticated */}
            <Route element={<ProtectedRoute />}>
                <Route path="/mon-planning" element={<MonPlanningPage />} />
                <Route path="/mes-repos"    element={<MesReposPage />} />

                {/* Admin only */}
                <Route element={<AdminRoute />}>
                    <Route path="/dashboard"  element={<DashboardPage />} />
                    <Route path="/users"      element={<UsersPage />} />
                    <Route path="/equipes"    element={<EquipesPage />} />
                    <Route path="/taches"     element={<TachesPage />} />
                    <Route path="/plannings"  element={<PlanningsPage />} />
                    <Route path="/repos"      element={<ReposPage />} />
                </Route>
            </Route>

            <Route path="*" element={<Navigate to="/login" />} />
        </Routes>
    );
}
