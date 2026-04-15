import { useSelector } from 'react-redux';
import { Navigate, Outlet } from 'react-redux';
import { Outlet, Navigate } from 'react-router-dom';

export default function AdminRoute() {
    const { user } = useSelector((state) => state.auth);
    return user?.role === 'Admin' ? <Outlet /> : <Navigate to="/mon-planning" />;
}