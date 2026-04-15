import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import api from '../../api/axios';

// Login
export const login = createAsyncThunk(
    'auth/login',
    async (credentials, { rejectWithValue }) => {
        try {
            const res = await api.post('/login', credentials);
            localStorage.setItem('token', res.data.token);
            return res.data;
        } catch (err) {
            return rejectWithValue(err.response?.data?.message || 'Erreur login');
        }
    }
);

// Logout
export const logout = createAsyncThunk(
    'auth/logout',
    async (_, { rejectWithValue }) => {
        try {
            await api.post('/logout');
            localStorage.removeItem('token');
        } catch (err) {
            return rejectWithValue(err.response?.data?.message);
        }
    }
);

// Me
export const fetchMe = createAsyncThunk(
    'auth/me',
    async (_, { rejectWithValue }) => {
        try {
            const res = await api.get('/me');
            return res.data;
        } catch (err) {
            return rejectWithValue(err.response?.data?.message);
        }
    }
);

const authSlice = createSlice({
    name: 'auth',
    initialState: {
        user:    null,
        token:   localStorage.getItem('token') || null,
        loading: false,
        error:   null,
    },
    reducers: {
        clearError: (state) => { state.error = null; }
    },
    extraReducers: (builder) => {
        builder
            // Login
            .addCase(login.pending,   (state) => { state.loading = true;  state.error = null; })
            .addCase(login.fulfilled, (state, action) => {
                state.loading = false;
                state.token   = action.payload.token;
                state.user    = action.payload.user;
            })
            .addCase(login.rejected,  (state, action) => {
                state.loading = false;
                state.error   = action.payload;
            })
            // Logout
            .addCase(logout.fulfilled, (state) => {
                state.user  = null;
                state.token = null;
            })
            // Me
            .addCase(fetchMe.fulfilled, (state, action) => {
                state.user = action.payload;
            });
    }
});

export const { clearError } = authSlice.actions;
export default authSlice.reducer;