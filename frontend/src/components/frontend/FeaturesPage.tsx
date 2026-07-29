import React from 'react';
import { Navigate } from 'react-router-dom';
import { useSettingsContext } from '../../context/SettingsContext';
import { FeatureGallerySection } from './FeatureGallerySection';

export const FeaturesPage: React.FC = () => {
  const { settings } = useSettingsContext();
  const enabled = settings.gallery?.enabled === true;
  const placement = settings.gallery?.placement ?? 'route';
  const showRoute = enabled && (placement === 'route' || placement === 'both');

  if (!showRoute) {
    return <Navigate to="/" replace />;
  }

  return <FeatureGallerySection variant="page" />;
};

export default FeaturesPage;
