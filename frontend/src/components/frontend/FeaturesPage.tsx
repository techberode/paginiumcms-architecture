import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useSettingsContext } from '../../context/SettingsContext';
import { FeatureGallerySection } from './FeatureGallerySection';
import { isGalleryPublicPath, normalizeGalleryPublicPath } from '../../utils/galleryPublicRoute';

export const FeaturesPage: React.FC = () => {
  const { pathname } = useLocation();
  const { settings } = useSettingsContext();
  const enabled = settings.gallery?.enabled === true;
  const placement = settings.gallery?.placement ?? 'route';
  const publicRoute = normalizeGalleryPublicPath(settings.gallery?.publicRoute);
  const showRoute = enabled && (placement === 'route' || placement === 'both');

  if (!showRoute) {
    return <Navigate to="/" replace />;
  }

  if (!isGalleryPublicPath(pathname, publicRoute)) {
    return <Navigate to={publicRoute} replace />;
  }

  return <FeatureGallerySection variant="page" />;
};

export default FeaturesPage;
