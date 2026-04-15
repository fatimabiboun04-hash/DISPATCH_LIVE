import { configureStore } from '@reduxjs/toolkit';
import authReducer     from '../features/auth/authSlice';
import userReducer     from '../features/users/userSlice';
import equipeReducer   from '../features/equipes/equipeSlice';
import tacheReducer    from '../features/taches/tacheSlice';
import planningReducer from '../features/plannings/planningSlice';
import reposReducer    from '../features/repos/reposSlice';

export const store = configureStore({
    reducer: {
        auth:     authReducer,
        users:    userReducer,
        equipes:  equipeReducer,
        taches:   tacheReducer,
        plannings: planningReducer,
        repos:    reposReducer,
    }
});